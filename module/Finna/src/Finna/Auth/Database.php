<?php
/**
 * Database authentication class
 *
 * PHP version 7
 *
 * Copyright (C) The National Library of Finland 2022.
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
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:authentication_handlers Wiki
 */
namespace Finna\Auth;

use VuFind\Exception\Auth as AuthException;

/**
 * Database authentication class
 *
 * @category VuFind
 * @package  Authentication
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:authentication_handlers Wiki
 */
class Database extends \VuFind\Auth\Database
{
    /**
     * Make sure username contains only allowed characters
     *
     * @param array $params request parameters
     *
     * @return void
     */
    protected function validateUsernameAndPassword($params)
    {
        parent::validateUsernameAndPassword($params);

        // Check that the username only contains allowed characters:
        $valid = preg_match(
            '/^(?!.*[._\-]{2})[A-ZÅÄÖa-zåäö0-9._\-]{3,50}$/',
            $params['username']
        );
        if (!$valid) {
            throw new AuthException('Username contains invalid characters');
        }
    }
}
