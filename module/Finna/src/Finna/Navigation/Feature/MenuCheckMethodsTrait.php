<?php

/**
 * Menu check methods trait
 *
 * PHP version 8
 *
 * Copyright (C) The National Library of Finland 2026.
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
 * along with this program; if not, see
 * <https://www.gnu.org/licenses/>.
 *
 * @category VuFind
 * @package  Navigation
 * @author   Aleksi Peebles <aleksi.peebles@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org Main Siteme
 */

namespace Finna\Navigation\Feature;

/**
 * Menu check methods trait
 *
 * @category VuFind
 * @package  Navigation
 * @author   Aleksi Peebles <aleksi.peebles@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org Main Siteme
 */
trait MenuCheckMethodsTrait
{
    /**
     * Combined configuration.
     *
     * @var array
     */
    protected array $combinedConfig;

    /**
     * Primo configuration.
     *
     * @var array
     */
    protected array $primoConfig;

    /**
     * Browse configuration.
     *
     * @var array
     */
    protected array $browseConfig;

    /**
     * Organisation info configuration.
     *
     * @var array
     */
    protected array $organisationInfoConfig;

    /**
     * Authority configuration.
     *
     * @var array
     */
    protected array $authorityConfig;

    /**
     * Set combined configuration.
     *
     * @param array $combinedConfig Combined configuration
     *
     * @return void
     */
    public function setCombinedConfig(array $combinedConfig): void
    {
        $this->combinedConfig = $combinedConfig;
    }

    /**
     * Set Primo configuration.
     *
     * @param array $primoConfig Primo configuration
     *
     * @return void
     */
    public function setPrimoConfig(array $primoConfig): void
    {
        $this->primoConfig = $primoConfig;
    }

    /**
     * Set browse configuration.
     *
     * @param array $browseConfig Browse configuration
     *
     * @return void
     */
    public function setBrowseConfig(array $browseConfig): void
    {
        $this->browseConfig = $browseConfig;
    }

    /**
     * Set organisation info configuration.
     *
     * @param array $organisationInfoConfig Organisation info configuration
     *
     * @return void
     */
    public function setOrganisationInfoConfig(array $organisationInfoConfig): void
    {
        $this->organisationInfoConfig = $organisationInfoConfig;
    }

    /**
     * Set authority configuration.
     *
     * @param array $authorityConfig Authority configuration
     *
     * @return void
     */
    public function setAuthorityConfig(array $authorityConfig): void
    {
        $this->authorityConfig = $authorityConfig;
    }

    /**
     * Check whether to show combined results item.
     *
     * @return bool
     */
    public function checkCombined(): bool
    {

        return (bool)($this->combinedConfig['General']['enabled'] ?? false);
    }

    /**
     * Check whether to show a Primo item.
     *
     * @return bool
     */
    public function checkPrimo(): bool
    {
        return !empty($this->primoConfig['Institutions']['onCampusRule'])
            && ($this->primoConfig['General']['enabled'] ?? true);
    }

    /**
     * Check whether to show browse database item.
     *
     * @return bool
     */
    public function checkBrowseDatabase(): bool
    {
        return (bool)($this->browseConfig['General']['Database'] ?? false);
    }

    /**
     * Check whether to show browse journal item.
     *
     * @return bool
     */
    public function checkBrowseJournal(): bool
    {
        return (bool)($this->browseConfig['General']['Journal'] ?? false);
    }

    /**
     * Check whether to show organisation info item.
     *
     * @return bool
     */
    public function checkOrganisationInfo(): bool
    {
        return (bool)($this->organisationInfoConfig['General']['enabled'] ?? false);
    }

    /**
     * Check whether to show authority item.
     *
     * @return bool
     */
    public function checkAuthority(): bool
    {
        return (bool)($this->authorityConfig['General']['enabled'] ?? false);
    }
}
