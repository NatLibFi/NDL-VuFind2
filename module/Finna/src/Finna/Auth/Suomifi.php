<?php
/**
 * Suomi.fi authentication module.
 *
 * PHP version 7
 *
 * Copyright (C) The National Library of Finland 2019.
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
 * @author   Samuli Sillanpää <samuli.sillanpaa@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org Main Page
 */
namespace Finna\Auth;

use VuFind\Exception\Auth as AuthException;

/**
 * Suomi.fi authentication module.
 *
 * @category VuFind
 * @package  Authentication
 * @author   Samuli Sillanpää <samuli.sillanpaa@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org Main Page
 */
class Suomifi extends Shibboleth
{
    /**
     * REMS service
     *
     * @var \Finna\RemsService\RemsService
     */
    protected $remsService;

    /**
     * Constructor
     *
     * @param \Zend\Session\ManagerInterface $sessionManager Session manager
     * @param \Finna\RemsService\RemsService $remsService    REMS service
     */
    public function __construct(
        \Zend\Session\ManagerInterface $sessionManager,
        $remsService = null
    ) {
        $this->sessionManager = $sessionManager;
        $this->remsService = $remsService;
    }

    /**
     * Attempt to authenticate the current user.  Throws exception if login fails.
     *
     * @param \Zend\Http\PhpEnvironment\Request $request Request object containing
     * account credentials.
     *
     * @throws AuthException
     * @return \VuFind\Db\Row\User Object representing logged-in user.
     */
    public function authenticate($request)
    {
        $result = parent::authenticate($request);

        // Store plain text username to session
        $shib = $this->getConfig()->Shibboleth;
        $username = $this->getServerParam($request, $shib->username, true);
        $session = new \Zend\Session\Container(
            'Shibboleth', $this->sessionManager
        );
        $session['username'] = $username;

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
        if ($this->remsService) {
            $this->remsService->onLogoutPre();
        }
        return parent::logout($url);
    }

    /**
     * Set configuration.
     *
     * @param \Zend\Config\Config $config Configuration to set
     *
     * @return void
     */
    public function setConfig($config)
    {
        // Replace Shibboleth config section with Shibboleth_suomifi
        $data = $config->toArray();
        $data['Shibboleth'] = $data['Shibboleth_suomifi'];
        $config = new \Zend\Config\Config($data);

        parent::setConfig($config);
    }

    /**
     * Get the URL to establish a session (needed when the internal VuFind login
     * form is inadequate).  Returns false when no session initiator is needed.
     *
     * @param string $target Full URL where external authentication method should
     * send user after login (some drivers may override this).
     *
     * @return bool|string
     */
    public function getSessionInitiator($target)
    {
        $url = parent::getSessionInitiator($target);
        if (!$url) {
            return $url;
        }
        // Set 'auth_method' to Suomifi
        $url = str_replace(
            'auth_method%3DShibboleth', 'auth_method%3DSuomifi', $url
        );
        return $url;
    }

    /**
     * Get a server parameter taking into account any environment variables
     * redirected by Apache mod_rewrite.
     *
     * @param \Zend\Http\PhpEnvironment\Request $request Request object containing
     * account credentials.
     * @param string                            $param   Parameter name
     *
     * @throws AuthException
     * @return mixed
     */
    protected function getServerParam(
        $request, $param, $neverHashUsername = false
    ) {
        $val = parent::getServerParam($request, $param);

        $config = $this->getConfig()->Shibboleth;
        if (!$neverHashUsername
            && $param === $config->username
            && ((bool)$config->hash_username ?? false)
        ) {
            $secret = $config->hash_secret ?? null;
            if (empty($secret)) {
                throw new AuthException('hash_secret not configured');
            }
            $val = hash_hmac('sha256', $val, $secret, false);
        }
        return $val;
    }
}
