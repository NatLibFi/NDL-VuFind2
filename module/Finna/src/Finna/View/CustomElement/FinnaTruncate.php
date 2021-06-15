<?php
/**
 * Finna-truncate custom element
 *
 * PHP version 7
 *
 * Copyright (C) The National Library of Finland 2021.
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
 * @package  CustomElements
 * @author   Aleksi Peebles <aleksi.peebles@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:recommendation_modules Wiki
 */
namespace Finna\View\CustomElement;

/**
 * Finna-truncate custom element
 *
 * @category VuFind
 * @package  CustomElements
 * @author   Aleksi Peebles <aleksi.peebles@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:recommendation_modules Wiki
 */
class FinnaTruncate extends AbstractBase
{
    /**
     * FinnaTruncate constructor.
     *
     * @param string $name    Element name
     * @param array  $options Options
     */
    public function __construct(string $name, array $options = [])
    {
        parent::__construct($name, $options);

        $labelElement = $this->dom->find('*[slot="label"]');
        if ($labelElement = $labelElement[0] ?? false) {
            $label = trim(strip_tags($labelElement->innerHtml()));
            if (!empty($label)) {
                $this->viewModel->setVariable('label', $label);
            }
            $this->removeSlotElement($labelElement);
        }

        $this->viewModel->setVariable(
            'content', $this->dom->firstChild()->innerHTML()
        );

        $this->viewModel->setTemplate('CustomElement/finna-truncate');
    }

    /**
     * Get names of attributes to set as view model variables.
     *
     * @return array Keyed array with attribute names as keys and variable names as
     *               values
     */
    protected static function getAttributeToVariableMap(): array
    {
        return ['rows' => 'rows'];
    }
}
