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

use Exception;
use Finna\Navigation\Feature\NavibarTrait;
use VuFind\I18n\Translator\TranslatorAwareInterface;

use function count;
use function in_array;

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
                    if ($submenuItem = $this->processNavibarItem($submenuItem, $items['id'])) {
                        $navibarItem['submenuItems'][] = $submenuItem;
                    }
                }
            } else {
                $navibarItem = $this->processNavibarItem($items['items'][0], $items['id']);
            }
            if ($navibarItem) {
                $navibarItems[] = $navibarItem;
            }
        }
        return $navibarItems;
    }

    /**
     * Process parsed navibar item to be compatible with header menu configuration.
     *
     * @param array  $navibarItem   Navibar item
     * @param string $navibarMenuId Navibar menu ID
     *
     * @return array|false
     */
    protected function processNavibarItem(array $navibarItem, string $navibarMenuId): array|false
    {
        $action = $navibarItem['action'] ?? null;
        if (!($url = $action['url'] ?? null)) {
            return false;
        }
        $processedItem = [];
        $processedItem['label'] = $navibarItem['label'];
        if ($desc = $navibarItem['desc'] ?? null) {
            $processedItem['description'] = $desc;
        }
        if ($action['route'] ?? false) {
            $options = ['name' => $url];
            $params = $action['routeParams'] ?? [];
            try {
                $processedItem['url'] = $this->router->assemble($params, $options);
            } catch (Exception) {
                // Invalid route, skip item.
                return false;
            }
        } else {
            $processedItem['url'] = $url;
        }
        if ($target = $action['target'] ?? null) {
            $processedItem['target'] = $target;
        }
        $excluded = $this->navibarConfig['__exclude_from_site_map_page__'] ?? [];
        $excludedFromMenu = (array)($excluded[$navibarMenuId] ?? []);
        if (
            in_array($navibarItem['id'], $excludedFromMenu)
            || in_array('__MENU__', $excludedFromMenu)
        ) {
            $processedItem['excludeFromSiteMapPage'] = true;
        }
        return $processedItem;
    }
}
