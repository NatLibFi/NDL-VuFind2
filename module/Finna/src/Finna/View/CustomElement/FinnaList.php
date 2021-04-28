<?php
/**
 * Finna-list custom element
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
 * Finna-list custom element
 *
 * @category VuFind
 * @package  CustomElements
 * @author   Aleksi Peebles <aleksi.peebles@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:recommendation_modules Wiki
 */
class FinnaList extends AbstractBase
{
    /**
     * Get the name of the element.
     *
     * @return string
     */
    public static function getName(): string
    {
        return 'finna-list';
    }

    /**
     * Get default values for view model variables.
     *
     * @return array
     */
    protected function getDefaultVariableValues(): array
    {
        return [
            'view' => 'grid',
            'description' => true,
            'title' => true,
            'date' => false,
            'allowCopy' => true,
            'limit' => '6',
            'showAllLink' => true,
        ];
    }

    /**
     * Get names of attributes to set as view model variables.
     *
     * @return array
     */
    protected function getVariableAttributes(): array
    {
        return array_merge(['id'], array_keys($this->getDefaultVariableValues()));
    }
}
