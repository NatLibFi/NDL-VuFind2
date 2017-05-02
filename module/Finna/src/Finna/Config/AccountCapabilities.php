<?php
/**
 * Class to determine which account capabilities are available, based on
 * configuration and other factors.
 *
 * PHP version 5
 *
 * Copyright (C) Villanova University 2015.
 * Copyright (C) The National Library of Finland 2016.
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
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @author   Samuli Sillanpää <samuli.sillanpaa@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org Main Site
 */
namespace Finna\Config;

/**
 * Class to determine which account capabilities are available, based on
 * configuration and other factors.
 *
 * @category VuFind
 * @package  Controller
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @author   Samuli Sillanpää <samuli.sillanpaa@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org Main Site
 */
class AccountCapabilities extends \VuFind\Config\AccountCapabilities
{
    /**
     * Get comment setting.
     *
     * @return string
     */
    public function getCommentSetting()
    {
        if (!$this->isAccountAvailable()) {
            return 'disabled';
        }

        return isset($this->config->Social->comments)
            && (!$this->config->Social->comments
            || $this->config->Social->comments === 'disabled')
            ? 'disabled' : 'enabled';
    }
}
