<?php

/**
 * ShortcutBar plugin.
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
 * @package  ShortcutBar
 * @author   Siiri Ylönen <siiri.ylonen@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org Main Site
 */

namespace Finna\View\Helper\Root;

use VuFind\Config\YamlReader;
use VuFind\Http\RouteHelper;

/**
 * ShortcutBar plugin.
 *
 * @category VuFind
 * @package  ShortcutBar
 * @author   Siiri Ylönen <siiri.ylonen@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org Main Site
 */
class ShortcutBar extends \Laminas\View\Helper\AbstractHelper
{
    use \VuFind\I18n\Translator\TranslatorAwareTrait;

    /**
     * YAML reader.
     *
     * @var YamlReader
     */
    protected $yamlReader;

    /**
     * Url helper.
     *
     * @var RouteHelper
     */
    protected $routeHelper;

    /**
     * Constructor.
     *
     * @param YamlReader  $yamlReader  YAML reader
     * @param RouteHelper $routeHelper URL helper
     */
    public function __construct(
        YamlReader $yamlReader,
        RouteHelper $routeHelper,
    ) {
        $this->yamlReader = $yamlReader;
        $this->routeHelper = $routeHelper;
    }

    /**
     * Returns a shortcut bar.
     *
     * @param string $name Name of the rendered shortcut bar.
     *
     * @return string
     */
    public function __invoke(string $name)
    {
        $settings = $this->getShortcutBarSettings($name);
        if (!$settings || !isset($settings['items'])) {
            return;
        }
        $attributeSettings = [];
        $attributeSettings['class'] = '';
        if ($attributes = $settings['attributes']) {
            foreach ($attributes as $key => $attribute) {
                if ($key === 'aria-label') {
                    $attributeSettings[$key] = $this->translate($attribute);
                    continue;
                }
                $attributeSettings[$key] = $attribute;
            }
        }
        $attributeSettings['class'] .= ' shortcut-bar-scrollable-list';
        $items = $this->getShortcutBarItems($settings['items']);
        $component = $this->getView()->plugin('component');
        return $component(
            'finna-scrollable-list',
            [
                'title' => $settings['title'] ?? '',
                'headingLevel' => $settings['headingLevel'] ?? '',
                'attributes' => $attributeSettings,
                'items' => $items,
            ]
        );
    }

    /**
     * Get settings for the items within a shortcut bar.
     *
     * @param array $items The item array.
     *
     * @return array
     */
    public function getShortcutBarItems(array $items): array
    {
        $shortcutBarItems = [];
        $lng = trim($this->translator->getLocale());
        foreach ($items as $item) {
            $itemSettings = [];
            if ($url = $item['url'] ?? '') {
                $itemSettings['href'] = $url[$lng] ?? $url['fi'] ?? $url;
            }
            if ($route = $item['route'] ?? '') {
                $itemSettings['href'] = $this->routeHelper->getUrlFromRoute(
                    $route,
                    $item['routeParams'] ?? [],
                    $item['queryParams'] ?? []
                );
            }
            $itemSettings['label'] = $item['label'] ?? 'link';
            if (isset($item['icon'])) {
                $itemSettings['icon'] = $item['icon'];
            }
            if (isset($item['iconElement'])) {
                $itemSettings['iconElement'] = $item['iconElement'];
            }
            if ($item['type'] ?? '' === 'dropdown') {
                $dropdownItems = $this->getShortcutBarItems($item['dropdownItems']);
                if ($dropdownItems) {
                    $itemSettings['dropdownItems'] = $dropdownItems;
                }
            }
            $itemSettings['type'] = $item['type'] ?? '';
            $shortcutBarItems[] = $itemSettings;
        }
        return $shortcutBarItems;
    }

    /**
     * Get settings for specific shortcut bar from ShortcutBar.yaml.
     *
     * @param string $name Name of the called shortcut bar.
     *
     * @return array
     */
    public function getShortcutBarSettings(string $name): array
    {
        $shortcutBarSettings = $this->yamlReader->get('ShortcutBar.yaml')[$name] ?? [];
        return $shortcutBarSettings;
    }
}
