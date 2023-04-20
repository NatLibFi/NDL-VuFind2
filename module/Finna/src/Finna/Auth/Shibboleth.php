<?php

/**
 * Shibboleth authentication module.
 *
 * PHP version 7
 *
 * Copyright (C) Villanova University 2010.
 * Copyright (C) The National Library of Finland 2015-2016.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301  USA
 *
 * @category VuFind
 * @package  Authentication
 * @author   Franck Borel <franck.borel@gbv.de>
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org Main Page
 */

namespace Finna\Auth;

use VuFind\Auth\Shibboleth\ConfigurationLoaderInterface;
use VuFind\Exception\Auth as AuthException;

/**
 * Shibboleth authentication module.
 *
 * @category VuFind
 * @package  Authentication
 * @author   Franck Borel <franck.borel@gbv.de>
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org Main Page
 */
class Shibboleth extends \VuFind\Auth\Shibboleth
{
    /**
     * ILS connection
     *
     * @var \Finna\ILS\Connection
     */
    protected $ils;

    /**
     * Constructor
     *
     * @param \Laminas\Session\ManagerInterface    $sessionManager      Session
     * manager
     * @param ConfigurationLoaderInterface         $configurationLoader Configuration
     * loader
     * @param \Laminas\Http\PhpEnvironment\Request $request             Http
     * request object
     * @param \Finna\ILS\Connection                $ils                 ILS
     * connection
     */
    public function __construct(
        \Laminas\Session\ManagerInterface $sessionManager,
        ConfigurationLoaderInterface $configurationLoader,
        \Laminas\Http\PhpEnvironment\Request $request,
        \Finna\ILS\Connection $ils
    ) {
        $this->sessionManager = $sessionManager;
        $this->configurationLoader = $configurationLoader;
        $this->request = $request;
        $this->ils = $ils;
    }

    /**
     * Attempt to authenticate the current user.  Throws exception if login fails.
     *
     * @param \Laminas\Http\PhpEnvironment\Request $request Request object containing
     * account credentials.
     *
     * @throws AuthException
     * @return \VuFind\Db\Row\User Object representing logged-in user.
     */
    public function authenticate($request)
    {
        // Check if username is set.
        $shib = $this->getConfig()->Shibboleth;
        $username = $this->getServerParam($request, $shib->username);
        if (empty($username)) {
            $this->debug(
                "No username attribute ({$shib->username}) present in request: "
                . print_r($request->getServer()->toArray(), true)
            );
            throw new AuthException('authentication_error_admin');
        }

        // Check if required attributes match up:
        foreach ($this->getRequiredAttributes($shib) as $key => $value) {
            $attrValue = $this->getServerParam($request, $key);
            if (!preg_match('/' . $value . '/', $attrValue)) {
                $this->debug(
                    "Attribute '$key' does not match required value '$value' in"
                    . ' request: ' . print_r($request->getServer()->toArray(), true)
                );
                throw new AuthException('authentication_error_invalid_attributes');
            }
        }

        // If we made it this far, we should log in the user!
        $user = $this->getUserTable()->getByUsername($username);

        // Variable to hold catalog password (handled separately from other
        // attributes since we need to use saveCredentials method to store it):
        $catPassword = null;

        // Has the user configured attributes to use for populating the user table?
        foreach ($this->attribsToCheck as $attribute) {
            if (isset($shib[$attribute])) {
                $value = $this->getAttribute($request, $shib[$attribute]);
                if ($attribute == 'email') {
                    $user->updateEmail($value);
                } elseif (
                    $attribute == 'cat_username' && isset($shib['prefix'])
                    && !empty($value)
                ) {
                    $user->cat_username = $shib['prefix'] . '.' . $value;
                } elseif ($attribute == 'cat_password') {
                    $catPassword = $value;
                } else {
                    $user->$attribute = ($value === null) ? '' : $value;
                }
            }
        }

        $idpParam = $shib->idpserverparam ?? self::DEFAULT_IDPSERVERPARAM;
        $idp = $this->getServerParam($request, $idpParam);
        if (!empty($shib->idp_to_ils_map[$idp])) {
            foreach (explode('|', $shib->idp_to_ils_map[$idp]) as $mapping) {
                $parts = explode(':', $mapping);
                $catUsername = $this->getServerParam($request, $parts[0]);
                $driver = $parts[1] ?? '';
                if (!$catUsername || !$driver) {
                    continue;
                }
                // Check whether the credentials work:
                $catUsername = "$driver.$catUsername";
                try {
                    if ($this->ils->patronLogin($catUsername, null)) {
                        $user->cat_username = $catUsername;
                        $this->debug(
                            "ILS account '$catUsername' linked to user '$username'"
                        );
                        break;
                    }
                    $this->debug(
                        "ILS account '$catUsername' not valid for user '$username'"
                    );
                } catch (\Exception $e) {
                    $this->logError(
                        'Failed to check username validity: ' . (string)$e
                    );
                }
            }
        }

        // Save credentials if applicable:
        if (!empty($user->cat_username)) {
            $user->saveCredentials(
                $user->cat_username,
                $catPassword ?? $user->getCatPassword()
            );
        }

        // Store logout URL in session:
        if (isset($shib->logout_attribute)) {
            $url = $this->getServerParam($request, $shib->logout_attribute);
            if ($url) {
                $session = new \Laminas\Session\Container(
                    'Shibboleth',
                    $this->sessionManager
                );
                $session['logoutUrl'] = $url;
            }
        }

        $this->storeShibbolethSession($request);

        // Save and return the user object:
        $user->save();
        return $user;
    }

    /**
     * Perform cleanup at logout time.
     *
     * @param string $url URL to redirect user to after logging out.
     *
     * @return string     Redirect URL (usually same as $url, but modified in
     * some authentication modules).
     */
    public function logout($url)
    {
        // Check for a dynamic logout url:
        $session
            = new \Laminas\Session\Container('Shibboleth', $this->sessionManager);
        if (!empty($session['logoutUrl'])) {
            $url = $session['logoutUrl'] . '?return=' . urlencode($url);
            return $url;
        }

        return parent::logout($url);
    }

    /**
     * Get a server parameter taking into account any environment variables
     * redirected by Apache mod_rewrite.
     *
     * @param \Laminas\Http\PhpEnvironment\Request $request Request object containing
     * account credentials.
     * @param string                               $param   Parameter name
     *
     * @return mixed
     */
    protected function getServerParam($request, $param)
    {
        return $request->getServer()->get(
            $param,
            $request->getServer()->get("REDIRECT_$param")
        );
    }
}
