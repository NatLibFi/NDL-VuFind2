<?php
/**
 * MultiAuth Authentication plugin
 *
 * PHP version 7
 *
 * Copyright (C) The National Library of Finland 2020.
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

/**
 * ChoiceAuth Authentication plugin
 *
 * This module enables a user to choose between two authentication methods.
 * choices are presented side-by-side and one is manually selected.
 *
 * See config.ini for more details
 *
 * @category VuFind
 * @package  Authentication
 * @author   Samuli Sillanpää <samuli.sillanpaa@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:authentication_handlers Wiki
 */
class ChoiceAuth extends \VuFind\Auth\ChoiceAuth
{
    /**
     * Get the route that is displayed in lightbox after the login has been
     * successfully performed and the page reloaded. Returns an array with
     * 'route' and 'params' keys.
     *
     * @return null|array
     */
    public function getPostLoginLightboxRoute()
    {
        try {
            return
                $this->proxyAuthMethod('getPostLoginLightboxRoute', func_get_args());
        } catch (\Exception $e) {
            return null;
        }
    }
}
