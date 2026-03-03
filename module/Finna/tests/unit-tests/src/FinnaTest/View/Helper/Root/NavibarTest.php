<?php

/**
 * Navibar test class
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
 * @package  Tests
 * @author   Aleksi Peebles <aleksi.peebles@helsinki.fi>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:testing:unit_tests Wiki
 */

namespace FinnaTest\View\Helper\Root;

use Finna\View\Helper\Root\Navibar;

/**
 * Navibar test class
 *
 * @category VuFind
 * @package  Tests
 * @author   Aleksi Peebles <aleksi.peebles@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:testing:unit_tests Wiki
 */
class NavibarTest extends \PHPUnit\Framework\TestCase
{
    protected Navibar $helper;

    /**
     * Get view helper to test.
     *
     * @param ?array $config       Menu configuration
     * @param array  $checkMethods Values to return for specific check methods
     *
     * @return Navibar
     */
    protected function getHelper(
        ?array $config = null,
        array $checkMethods = []
    ): Navibar {
        if (isset($this->helper)) {
            return $this->helper;
        }

        if (null === $config) {
            $config = $this->getDefaultMenuConfig();
        }

        $navibar = $this->getMockBuilder(Navibar::class)
            ->onlyMethods(array_keys($this->getNavibarCheckMethods()))
            ->getMock();
        $navibar->method('checkCombined')
            ->willReturn($checkMethods['checkCombined'] ?? true);
        $navibar->method('checkPrimo')
            ->willReturn($checkMethods['checkPrimo'] ?? true);
        $navibar->method('checkBrowseDatabase')
            ->willReturn($checkMethods['checkBrowseDatabase'] ?? true);
        $navibar->method('checkBrowseJournal')
            ->willReturn($checkMethods['checkBrowseJournal'] ?? true);
        $navibar->method('checkOrganisationInfo')
            ->willReturn($checkMethods['checkOrganisationInfo'] ?? true);
        $navibar->method('checkAuthority')
            ->willReturn($checkMethods['checkAuthority'] ?? true);

        $navibar->setNavibarConfig($config);
        $this->helper = $navibar;

        return $navibar;
    }

    /**
     * Test navibar with all conditional menu items enabled.
     *
     * @return void
     */
    public function testAllMenuItemsEnabled(): void
    {
        $menuItems = $this->getHelper()->getMenuItems('fi');
        $this->assertEquals('search', $menuItems[0]['id']);
        $this->assertCount(6, $menuItems[0]['items']);
        $this->assertEquals('about_us', $menuItems[1]['id']);
        $this->assertCount(1, $menuItems[1]['items']);
    }

    /**
     * Test navibar with all conditional menu items disabled.
     *
     * @return void
     */
    public function testAllMenuItemsDisabled(): void
    {
        $menuItems = $this->getHelper(
            $this->getDefaultMenuConfig(),
            $this->getNavibarCheckMethods(false)
        )->getMenuItems('fi');
        $this->assertEmpty($menuItems);
    }

    /**
     * Get default menu configuration for tests.
     *
     * @return array
     */
    protected function getDefaultMenuConfig(): array
    {
        return [
            'search' => [
                'combined_search' => 'combined-home',
                'pci_search' => 'primo-home',
                'pci_advanced' => 'primo-advanced',
                'browse-database' => 'browse-database',
                'browse-journal' => 'browse-journal',
                'authority_search' => 'authority-home',
            ],
            'about_us' => [
                'organisation' => 'organisationinfo-home',
            ],
        ];
    }

    /**
     * Get all check methods.
     *
     * @param bool $value Value for the check methods to return
     *
     * @return bool[]
     */
    protected function getNavibarCheckMethods(bool $value = true): array
    {
        return [
            'checkCombined' => $value,
            'checkPrimo' => $value,
            'checkBrowseDatabase' => $value,
            'checkBrowseJournal' => $value,
            'checkOrganisationInfo' => $value,
            'checkAuthority' => $value,
        ];
    }
}
