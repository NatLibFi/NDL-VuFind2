<?php
/**
 * VuFind Recaptcha controller plugin
 *
 * PHP version 5
 *
 * Copyright (C) The National Library of Finland 2017.
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
 * @package  Plugin
 * @author   Joni Nevalainen <joni.nevalainen@gofore.com>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org Main Site
 */
namespace Finna\Controller\Plugin;

/**
 * Recaptcha controller plugin.
 *
 * @category VuFind
 * @package  Plugin
 * @author   Joni Nevalainen <joni.nevalainen@gofore.com>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/
 */
class Recaptcha extends \VuFind\Controller\Plugin\Recaptcha
{
    /**
     * Bypassed authentication methods
     *
     * @var array
     */
    protected $byPassCaptcha = [];

    /**
     * Recaptcha constructor.
     *
     * @param \ZendService\ReCaptcha\ReCaptcha $r      ReCaptcha object
     * @param \VuFind\Config                   $config Config file
     *
     * @return Recaptcha
     */
    public function __construct($r, $config)
    {
        parent::__construct($r, $config);
        if (!empty($config->Captcha->byPassCaptcha)) {
            $trimLowercase = function ($str) {
                return strtolower(trim($str));
            };

            $byPassCaptcha = $config->Captcha->byPassCaptcha->toArray();
            foreach ($byPassCaptcha as $domain => $authMethods) {
                $this->byPassCaptcha[$domain] = array_map(
                    $trimLowercase,
                    explode(',', $authMethods)
                );
            }
        }
    }

    /**
     * Return whether a specific form is set for Captcha in the config. Takes into
     * account authentication types which should be bypassed.
     *
     * @param string|bool $domain The specific config term are we checking; ie. "sms"
     *
     * @return bool
     */
    public function active($domain = false)
    {
        if (!$domain || empty($this->byPassCaptcha[$domain])) {
            return parent::active($domain);
        }

        $authManager = $this->getController()
            ->getServiceLocator()
            ->get('VuFind\AuthManager');
        $user = $authManager->isLoggedIn();
        return $user
            ? !in_array(
                strtolower($user->finna_auth_method),
                $this->byPassCaptcha[$domain]
            )
            : parent::active($domain);
    }
}