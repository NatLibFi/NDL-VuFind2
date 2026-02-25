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

use Finna\View\Helper\Root\Authority;
use Finna\View\Helper\Root\Browse;
use Finna\View\Helper\Root\Combined;
use Finna\View\Helper\Root\Navibar;
use Finna\View\Helper\Root\Primo;
use Laminas\Router\Http\TreeRouteStack;
use VuFind\Config\Config;
use VuFind\View\Helper\Root\Translate;
use VuFind\View\Helper\Root\TranslationEmpty;
use VuFindTest\Feature\ViewTrait;

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
    use ViewTrait;

    protected Navibar $helper;

    /**
     * Get view helper to test.
     *
     * @param ?Config $config       Menu configuration
     * @param array   $checkMethods Values to return for specific check methods
     *
     * @return Navibar
     */
    protected function getHelper(
        ?Config $config = null,
        array $checkMethods = []
    ): Navibar {
        if (isset($this->helper)) {
            return $this->helper;
        }

        if (null === $config) {
            $config = $this->getDefaultMenuConfig();
        }

        $combined = $this->createMock(Combined::class);
        $combined->method('isAvailable')
            ->willReturn($checkMethods['checkCombined'] ?? true);
        $primo = $this->createMock(Primo::class);
        $primo->method('isAvailable')
            ->willReturn($checkMethods['checkPrimo'] ?? true);
        $browse = $this->createMock(Browse::class);
        $browse->method('isAvailable')->willReturnCallback(
            function ($type) use ($checkMethods) {
                return match ($type) {
                    'Database' => $checkMethods['checkBrowseDatabase'] ?? true,
                    'Journal' =>  $checkMethods['checkBrowseJournal'] ?? true,
                };
            }
        );
        $organisationInfo = $this->createMock(\Finna\View\Helper\Root\OrganisationInfo::class);
        $organisationInfo->method('isAvailable')
            ->willReturn($checkMethods['checkOrganisationInfo'] ?? true);
        $authority = $this->createMock(Authority::class);
        $authority->method('isAvailable')
            ->willReturn($checkMethods['checkAuthority'] ?? true);

        $view = $this->getPhpRenderer(
            [
                'translate' => $this->createMock(Translate::class),
                'translationEmpty' => $this->createMock(TranslationEmpty::class),
                'combined' => $combined,
                'primo' => $primo,
                'browse' => $browse,
                'organisationInfo' => $organisationInfo,
                'authority' => $authority,
            ],
            'finna2'
        );

        $navibar = new Navibar(
            $config,
            $this->createMock(\Finna\OrganisationInfo\OrganisationInfo::class),
            $this->createMock(TreeRouteStack::class),
        );
        $navibar->setView($view);
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
     * @return Config
     */
    protected function getDefaultMenuConfig(): Config
    {
        return new Config([
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
        ]);
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
