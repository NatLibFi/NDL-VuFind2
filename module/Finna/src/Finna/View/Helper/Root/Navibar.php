<?php

/**
 * Navibar view helper
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
 * @package  View_Helpers
 * @author   Samuli Sillanpää <samuli.sillanpaa@helsinki.fi>
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org   Main Site
 */

namespace Finna\View\Helper\Root;

use Finna\Navigation\Feature\NavibarTrait;
use Laminas\View\Helper\AbstractHelper;
use VuFind\I18n\Translator\TranslatorAwareInterface;

/**
 * Navibar view helper
 *
 * @category VuFind
 * @package  View_Helpers
 * @author   Samuli Sillanpää <samuli.sillanpaa@helsinki.fi>
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org   Main Site
 *
 * @deprecated Use \VuFind\View\Helper\Root\Section instead
 */
class Navibar extends AbstractHelper implements TranslatorAwareInterface
{
    use NavibarTrait;

    /**
     * View helpers
     *
     * @var array
     */
    protected $viewHelpers = [];

    /**
     * Current language
     *
     * @var string
     */
    protected $language;

    /**
     * Returns Navibar view helper.
     *
     * @return FInna\View\Helper\Root\Navibar
     */
    public function __invoke()
    {
        return $this;
    }

    /**
     * Returns rendered navibar layout.
     *
     * @return string
     */
    public function render()
    {
        return $this->getView()->render('navibar.phtml');
    }

    /**
     * Returns menu items as an associative array where each item consists of:
     *    string  $label       Label (untranslated)
     *    string  $url         Url
     *    boolean $route       True if url is a route name.
     *                         False if url is a literal link.
     *    array   $routeParams Route parameters as a key-value pairs.
     *
     * @param string $lng Language code
     *
     * @return Array
     */
    public function getMenuItems($lng)
    {
        if (!$this->menuItems || $lng != $this->language) {
            $this->language = $lng;
            $this->parseMenuConfig($lng);
        }
        return $this->menuItems;
    }

    /**
     * Constructs an url for a menu item that may be used in the template.
     *
     * @param array $data menu item configuration
     *
     * @return string
     */
    public function getMenuItemUrl(array $data)
    {
        $action = $data['action'];
        $target = $action['target'] ?? null;
        if (!$action || empty($action['url'])) {
            return null;
        }
        if (!$action['route']) {
            return ['url' => $action['url'], 'target' => $target];
        }

        $urlHelper = $this->getViewHelper('url');
        try {
            $url = isset($action['routeParams'])
                ? $urlHelper($action['url'], $action['routeParams'])
                : $urlHelper($action['url']);
            return ['url' => $url, 'target' => $target];
        } catch (\Exception $e) {
        }

        return null;
    }

    /**
     * Return view helper
     *
     * @param string $id Helper id
     *
     * @return \Laminas\View\Helper
     */
    protected function getViewHelper($id)
    {
        if (!isset($this->viewHelpers[$id])) {
            $this->viewHelpers[$id] = $this->getView()->plugin($id);
        }
        return $this->viewHelpers[$id];
    }
}
