<?php

/**
 * HeaderBar section plugin
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
 * @link     https://vufind.org Main Site
 */

namespace Finna\Navigation;

use Finna\Navigation\Feature\NavibarTrait;
use VuFind\I18n\Translator\TranslatorAwareInterface;

use function count;

/**
 * HeaderBar section plugin
 *
 * @category VuFind
 * @package  Navigation
 * @author   Aleksi Peebles <aleksi.peebles@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org Main Site
 */
class HeaderBar extends \VuFind\Navigation\HeaderBar implements TranslatorAwareInterface
{
    use NavibarTrait;

    /**
     * Process or filter group.
     *
     * @param array $group Group to process
     *
     * @return array|false Processed group or false if group should be filtered
     */
    protected function processGroup(array $group): array|false
    {
        if ($group['navibarIni'] ?? false) {
            $group['MenuItems'] = array_merge(
                $this->getNavibarItems(),
                $group['MenuItems'] ?? []
            );
        }
        return parent::processGroup($group);
    }

    /**
     * Get navibar items.
     *
     * @return array
     */
    protected function getNavibarItems(): array
    {
        $navibarItems = [];
        $this->parseMenuConfig($this->localeSettings->getUserLocale());
        foreach ($this->menuItems as $items) {
            if (count($items['items']) > 1) {
                $navibarItem = [
                    'label' => $items['label'],
                    'submenuItems' => [],
                ];
                foreach ($items['items'] as $submenuItem) {
                    $navibarItem['submenuItems'][] = $this->processNavibarItem($submenuItem);
                }
            } else {
                $navibarItem = $this->processNavibarItem($items['items'][0]);
            }
            $navibarItems[] = $navibarItem;
        }
        return $navibarItems;
    }

    /**
     * Process parsed navibar item to be compatible with header menu configuration.
     *
     * @param array $navibarItem Navibar item
     *
     * @return array
     */
    protected function processNavibarItem(array $navibarItem): array
    {
        $processedItem = [];
        $processedItem['label'] = $navibarItem['label'];
        $processedItem['description'] = $navibarItem['desc'] ?? '';
        if ($navibarItem['action']['route'] ?? false) {
            $processedItem['route'] = $navibarItem['action']['url'];
            $processedItem['routeParams'] = $navibarItem['action']['routeParams'] ?? [];
        } else {
            $processedItem['url'] = $navibarItem['action']['url'];
        }
        return $processedItem;
    }
}
