<?php
/**
 * OAuth2 controller factory.
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
 * @package  Controller
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org Main Site
 */
namespace Finna\Controller;

use VuFind\Config\Locator;

/**
 * OAuth2 controller factory.
 *
 * @category VuFind
 * @package  Controller
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org Main Site
 */
class OAuth2ControllerFactory extends \VuFind\Controller\OAuth2ControllerFactory
{
    /**
     * Overridable wrapper for Locator::getConfigPath
     *
     * @param string  $filename Config file name
     * @param ?string $path     Path relative to VuFind base (optional; use null for
     * default)
     * @param int     $mode     Whether to check for local file, base file or both
     *
     * @return ?string
     */
    public static function getConfigPath(
        $filename,
        $path = null,
        int $mode = Locator::MODE_AUTO
    ): ?string {
        $result = null;
        if (Locator::MODE_BASE !== $mode) {
            $result = Locator::getLocalConfigPath($filename, $path);
            if (null === $result && null === $path) {
                $result
                    = Locator::getLocalConfigPath($filename, 'config/finna');
            }
        }
        if (null === $result && Locator::MODE_LOCAL !== $mode) {
            $result = Locator::getBaseConfigPath($filename);
        }

        return $result;
    }
}
