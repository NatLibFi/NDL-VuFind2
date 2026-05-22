<?php

/**
 * Navibar trait.
 *
 * PHP version 8
 *
 * Copyright (C) The National Library of Finland 2014-2026.
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
 * @author   Samuli Sillanpää <samuli.sillanpaa@helsinki.fi>
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org Main Siteme
 */

namespace Finna\Navigation\Feature;

use Laminas\Http\Request;
use Laminas\Router\Http\TreeRouteStack;
use VuFind\Http\ServerUrlHelper;
use VuFind\I18n\Translator\TranslatorAwareTrait;

use function count;
use function is_string;
use function strlen;

/**
 * Navibar trait.
 *
 * @category VuFind
 * @package  Navigation
 * @author   Samuli Sillanpää <samuli.sillanpaa@helsinki.fi>
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org Main Site
 */
trait NavibarTrait
{
    use MenuCheckMethodsTrait;
    use TranslatorAwareTrait;

    /**
     * Navibar configuration.
     *
     * @var array
     */
    protected array $navibarConfig;

    /**
     * Router object.
     *
     * @var TreeRouteStack
     */
    protected TreeRouteStack $router;

    /**
     * Server URL helper.
     *
     * @var ServerUrlHelper
     */
    protected ServerUrlHelper $serverUrlHelper;

    /**
     * Menu items.
     *
     * @var array
     */
    protected $menuItems;

    /**
     * Set navibar configuration.
     *
     * @param array $navibarConfig Navibar configuration
     *
     * @return void
     */
    public function setNavibarConfig(array $navibarConfig): void
    {
        $this->navibarConfig = $navibarConfig;
    }

    /**
     * Set router.
     *
     * @param TreeRouteStack $router Router
     *
     * @return void
     */
    public function setRouter(TreeRouteStack $router): void
    {
        $this->router = $router;
    }

    /**
     * Set server URL helper.
     *
     * @param ServerUrlHelper $serverUrlHelper Server URL helper
     *
     * @return void
     */
    public function setServerUrlHelper(ServerUrlHelper $serverUrlHelper): void
    {
        $this->serverUrlHelper = $serverUrlHelper;
    }

    /**
     * Returns a url for changing the site language.
     *
     * The url is constructed by appending 'lng' query parameter
     * to the current page url.
     * Note: the returned url does not include possible hash (anchor),
     * which is inserted on the client-side.
     * /themes/finna2/js/finna.js::initAnchorNavigationLinks
     *
     * @param string $lng Language code
     *
     * @return string
     */
    public function getLanguageUrl($lng)
    {
        // Clone the URI so that we don't manipulate current request:
        $url = clone $this->router->getRequestUri();
        $params = $url->getQueryAsArray();
        $params['lng'] = $lng;
        $url->setQuery(http_build_query($params));
        return $url->isValid() ? $url->toString() : '';
    }

    /**
     * Check if a URL points to the current page.
     *
     * @param string $url URL
     *
     * @return bool
     */
    public function isCurrentPage(string $url): bool
    {
        $requestUri = $this->router->getRequestUri();
        return $url === (string)$requestUri
            || $url === $requestUri->getPath();
    }

    /**
     * Internal function for parsing menu configuration.
     *
     * @param string $lng Language code
     *
     * @return void
     */
    protected function parseMenuConfig($lng)
    {
        $parseUrl = function ($url) {
            if (!$url) {
                return null;
            }
            $url = trim($url);

            $data = [];
            if (str_contains($url, ',')) {
                [$url, $target] = explode(',', $url, 2);
                $url = trim($url);
                $data['target'] = trim($target);
            }

            if (preg_match('/^(http|https):\/\//', $url)) {
                // external url
                $data['url'] = $url;
                $data['route'] = false;
                return $data;
            }

            $data['route'] = true;
            if (strncmp($url, '/', 1) === 0) {
                $url = $this->serverUrlHelper->getBaseUrl() . $this->router->getBaseUrl() . $url;
                $request = new Request();
                $request->setUri($url);
                $routeMatch = $this->router->match($request);
                if ($routeMatch != null) {
                    $data['routeParams'] = $routeMatch->getParams();
                    $data['url'] = $routeMatch->getMatchedRouteName();
                    return $data;
                }
            }

            $needle = 'content-';
            if (($pos = strpos($url, $needle)) === 0) {
                // Content pages do not have static routes, so we
                // need to add required route parameters for url view helper.
                $page = substr($url, $pos + strlen($needle));
                $data['routeParams'] = [];
                $data['routeParams']['page'] = $page;
                $url = 'content-page';
            }

            $data['url'] = $url;
            return $data;
        };

        $result = [];
        $menuConfig = $this->getMenuData($this->navibarConfig);
        $menuData = $menuConfig['menuData'];
        $sortData = $menuConfig['sortData'];

        foreach ($menuData as $menuKey => $items) {
            $item = [
                'id' => $menuKey, 'label' => "menu_$menuKey",
            ];

            $desc = 'menu_' . $menuKey . '_desc';
            if ($this->translate($desc, null, false) !== false) {
                $item['desc'] = $desc;
            }

            $options = [];
            foreach ($items as $itemKey => $action) {
                if (!is_string($action)) {
                    $action = $action[$lng] ?? '';
                }

                if (strncmp($action, 'metalib-', 8) === 0) {
                    // Discard MetaLib menu items
                    continue;
                }

                $option = [
                    'id' => $itemKey, 'label' => "menu_$itemKey",
                    'action' => $parseUrl($action),
                ];

                $desc = 'menu_' . $itemKey . '_desc';
                if (!$this->translationEmpty($desc)) {
                    $option['desc'] = $desc;
                }
                $options[] = $option;
            }
            if (empty($options)) {
                continue;
            } else {
                $item['items'] = $options;
                $result[] = $item;
            }
        }

        $menuItems = $this->sortMenuItems($result, $sortData);

        foreach ($menuItems as $menuKey => $option) {
            foreach ($option['items'] as $itemKey => $item) {
                if (!$item['action'] || !$this->menuItemEnabled($item)) {
                    unset($menuItems[$menuKey]['items'][$itemKey]);
                }
            }
            $menuItems[$menuKey]['items']
                = array_values($menuItems[$menuKey]['items']);

            if (
                isset($menuItems[$menuKey]['items'])
                && empty($menuItems[$menuKey]['items'])
            ) {
                unset($menuItems[$menuKey]);
            }
        }

        $this->menuItems = $menuItems;
    }

    /**
     * Check if menu item may be enabled.
     *
     * @param array $item Menu item configuration
     *
     * @return bool
     */
    protected function menuItemEnabled(array $item): bool
    {
        $action = $item['action'];
        if (!$action) {
            return false;
        }
        if (empty($action['route'])) {
            return true;
        }

        $url = $action['url'];

        if (strncmp($url, 'combined-', 9) === 0) {
            return $this->checkCombined();
        }
        if (strncmp($url, 'metalib-', 8) === 0) {
            return false;
        }
        if (strncmp($url, 'primo-', 6) === 0) {
            return $this->checkPrimo();
        }
        if ($url === 'browse-database') {
            return $this->checkBrowseDatabase();
        }
        if ($url === 'browse-journal') {
            return $this->checkBrowseJournal();
        }
        if ($url === 'organisationinfo-home') {
            return $this->checkOrganisationInfo();
        }
        if ($url === 'authority-home') {
            return $this->checkAuthority();
        }
        return true;
    }

    /**
     * Separate menu data from menu order data (__[menu]_sort__ sections).
     *
     * Returns an associative array with keys:
     *  'menuData' Menu items
     *  'sortData' Order data
     *  'excludeData' Exclude from site map page data
     *
     * @param array $config Menu configuration
     *
     * @return array
     */
    protected function getMenuData($config)
    {
        $menuData = $sortDataOrder = $sortData = $excludeData = [];

        foreach ($config as $menuKey => $items) {
            if ($menuKey === 'Parent_Config') {
                continue;
            }

            if (!count($items)) {
                continue;
            }

            if (preg_match('/^__(.*)_sort__$/', $menuKey, $matches)) {
                // Sort section
                $menuKey = $matches[1];
                // Re-order menu-level sort entries in descending order
                asort($items);
                $sortData[$menuKey] = $items;

                if (isset($items['__MENU__'])) {
                    // Top-level menu position
                    $sortDataOrder[$items['__MENU__']] = $menuKey;
                }
                continue;
            }

            if ($menuKey === '__exclude_from_site_map_page__') {
                $excludeData = $items;
                continue;
            }

            // Menu section
            $menuData[$menuKey] = $items;
        }

        // Re-order top-level sort entries in descending order
        $sortDataProcessed = [];
        ksort($sortDataOrder);

        foreach ($sortDataOrder as $menuKey) {
            $sortDataProcessed[$menuKey] = $sortData[$menuKey];
            unset($sortData[$menuKey]);
        }
        $sortData = array_merge($sortDataProcessed, $sortData);

        return [
            'menuData' => $menuData,
            'sortData' => $sortData,
            'excludeData' => $excludeData,
        ];
    }

    /**
     * Sort menu items.
     *
     * @param array $items Menu items
     * @param array $order Ordering
     *
     * @return array Sorted items
     */
    protected function sortMenuItems($items, $order)
    {
        foreach ($order as $menuKey => $order) {
            $menuPosition
                = $this->getItemIndex($items, $menuKey);
            if ($menuPosition === null) {
                continue;
            }
            if (isset($order['__MENU__'])) {
                // Re-position top-level menu
                $position = $order['__MENU__'];
                $items = $this->moveItem(
                    $items,
                    $menuPosition,
                    $position
                );
                $menuPosition = $position;
                unset($order['__MENU__']);
            }
            foreach ($order as $item => $position) {
                // Re-position single menu item
                if ($menuPosition === null) {
                    continue;
                }
                if (!isset($items[$menuPosition])) {
                    continue;
                }
                $currentPosition = $this->getItemIndex(
                    $items[$menuPosition]['items'],
                    $item
                );
                if ($currentPosition === null) {
                    continue;
                }
                $items[$menuPosition]['items']
                    = $this->moveItem(
                        $items[$menuPosition]['items'],
                        $currentPosition,
                        $position
                    );
            }
        }
        return $items;
    }

    /**
     * Get menu item index.
     *
     * @param array  $items Menu items
     * @param string $id    Menu item id
     *
     * @return mixed null|int
     */
    protected function getItemIndex($items, $id)
    {
        $cnt = 0;
        foreach ($items as $item) {
            if ($item['id'] === $id) {
                return $cnt;
            }
            $cnt++;
        }
        return null;
    }

    /**
     * Move menu item.
     *
     * @param array $items Menu items
     * @param int   $from  From (index)
     * @param int   $to    To (index)
     *
     * @return array Items
     */
    protected function moveItem($items, $from, $to)
    {
        if ($from < 0 || $to < 0) {
            return $items;
        }
        $move = array_splice($items, $from, 1);
        array_splice($items, $to, 0, $move);
        return $items;
    }

    /**
     * Check if a translation is empty.
     *
     * Code from TranslationEmpty::__invoke().
     *
     * @param string|object $str             String to translate
     * @param string[]      $fallbackDomains Text domains to check if no match is found in
     *                                       the domain specified in $target
     *
     * @return bool
     */
    protected function translationEmpty($str, $fallbackDomains = [])
    {
        $result = $this->translate($str, [], '', false, $fallbackDomains);
        // Existing empty translations will result in &#x200C, otherwise the default
        // '' is returned
        return $result === ''
            || $result === html_entity_decode('&#x200C;', ENT_NOQUOTES, 'UTF-8');
    }
}
