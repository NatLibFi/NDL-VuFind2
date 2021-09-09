<?php
/**
 * Matomo web analytics view helper for Matomo versions >= 4
 *
 * PHP version 7
 *
 * Copyright (C) The National Library of Finland 2014-2021.
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
 * @package  View_Helpers
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @author   Samuli Sillanpää <samuli.sillanpaa@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org Main Site
 */
namespace Finna\View\Helper\Root;

/**
 * Matomo web analytics view helper for Matomo versions >= 4
 *
 * @category VuFind
 * @package  View_Helpers
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @author   Samuli Sillanpää <samuli.sillanpaa@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org Main Site
 */
class Matomo extends \VuFind\View\Helper\Root\Matomo
{
    /**
     * Custom data added using the addCustomData method.
     *
     * @var array
     */
    protected $additionalCustomData = [];

    /**
     * Add custom data to be tracked.
     *
     * @param string $name  Name
     * @param string $value Value
     *
     * @return void
     */
    public function addCustomData($name, $value)
    {
        $this->additionalCustomData[$name] = $value;
    }

    /**
     * Get the URL for the current page
     *
     * @return string
     */
    protected function getPageUrl(): string
    {
        // Prettify image popup page URL (AJAX/JSON?method=... > /record/[id]/image
        if ($this->calledFromImagePopup()
            && !empty($this->params['recordUrl'])
        ) {
            return $this->params['recordUrl'] . '/image';
        }
        return parent::getPageUrl();
    }

    /**
     * Convert a custom data array to JavaScript code
     *
     * @param array $customData Custom data
     *
     * @return string JavaScript Code Fragment
     */
    protected function getCustomVarsCode(array $customData): string
    {
        if (!empty($this->additionalCustomData)) {
            $customData = array_merge($customData, $this->additionalCustomData);
        }
        if (!empty($this->params['customData'])) {
            $customData = array_merge($customData, $this->params['customData']);
        }

        return parent::getCustomVarsCode($customData);
    }

    /**
     * Check if the view helper was called from image popup template.
     *
     * @return bool
     */
    protected function calledFromImagePopup(): bool
    {
        return isset($this->params['action'])
            && $this->params['action'] == 'imagePopup'
            && isset($this->params['record']);
    }
}
