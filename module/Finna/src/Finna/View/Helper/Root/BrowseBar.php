<?php

/**
 * BrowseBar plugin.
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
 * @package  BrowseBar
 * @author   Aleksi Peebles <aleksi.peebles@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org Main Site
 */

namespace Finna\View\Helper\Root;

use VuFind\Config\YamlReader;

/**
 * BrowseBar plugin.
 *
 * @category VuFind
 * @package  BrowseBar
 * @author   Aleksi Peebles <aleksi.peebles@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org Main Site
 */
class BrowseBar extends \Laminas\View\Helper\AbstractHelper
{
    /**
     * YAML reader.
     *
     * @var YamlReader
     */
    protected $yamlReader;

    /**
     * Constructor.
     *
     * @param YamlReader $yamlReader YAML reader
     */
    public function __construct(
        YamlReader $yamlReader,
    ) {
        $this->yamlReader = $yamlReader;
    }

    /**
     * Get settings for the items within a browse bar.
     *
     * @param array $items The item array.
     *
     * @return array
     */
    public function getBrowseBarItems(array $items): array
    {
        $itemsSettings = [];
        foreach ($items as $item) {
            $itemSettings = [
                'link' => $item['link'] ?? '',
                'label' => $item['label'] ?? 'link',
            ];
            $iconText = '';
            if (isset($item['icon'])) {
                $iconText = 'icon';
            } elseif (isset($item['iconElement'])) {
                $iconText = 'iconElement';
            }
            if ($iconText) {
                $itemSettings[$iconText] = $item[$iconText];
            }
            if ($item['type'] ?? '' === 'dropdown') {
                $dropdownItems = $this->getBrowseBarItems($item['dropdownItems']);
                if ($dropdownItems) {
                    $itemSettings['dropdownItems'] = $dropdownItems;
                }
            }
            $itemSettings['type'] = $item['type'] ?? '';
            $itemsSettings[] = $itemSettings;
        }
        return $itemsSettings;
    }

    /**
     * Render a browse bar component.
     *
     * @param string $name Name of the rendered browse bar.
     *
     * @return string
     */
    public function renderBrowseBar(string $name)
    {
        $settings = $this->getBrowseBarSettings($name);
        if (!$settings && !$settings['items']) {
            return;
        }
        $attributeSettings = [];
        if ($attributes = $settings['attributes']) {
            foreach ($attributes as $key => $attribute) {
                $attributeSettings[$key] = $attribute;
            }
        }
        $items = $this->getBrowseBarItems($settings['items']);
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
     * Get settings for specific browse bar from BrowseBar.yaml.
     *
     * @param string $name Name of the called browse bar.
     *
     * @return array
     */
    public function getBrowseBarSettings(string $name): array
    {
        $browseBarSettings = $this->yamlReader->get('BrowseBar.yaml')[$name] ?? [];
        return $browseBarSettings;
    }
}
