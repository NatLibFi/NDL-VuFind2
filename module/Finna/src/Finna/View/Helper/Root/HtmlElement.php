<?php
/**
 * HtmlElement helper
 *
 * PHP version 7
 *
 * Copyright (C) The National Library of Finland 2018.
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
 * @package  Content
 * @author   Juha Luoma <juha.luoma@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/vufind2:developer_manual Wiki
 */
namespace Finna\View\Helper\Root;

/**
 * HtmlElement helper
 *
 * @category VuFind
 * @package  Content
 * @author   Juha Luoma <juha.luoma@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/vufind2:developer_manual Wiki
 */
class HtmlElement extends \Zend\View\Helper\AbstractHelper
{
    /**
     * List of attributes with no values
     */
    protected $booleanAttributes = [
        'selected',
        'disabled',
        'checked',
        'open',
        'multiple'
    ];

    protected $elementBase = [];

    /**
     * Adds a base-element to $this->elementBase array
     * identified by $identifier
     *
     * @param string $identifier key for the element in base data
     * @param array  $data       attributes of the element
     *
     * @return void
     */
    public function base(string $identifier, array $data)
    {
        $this->elementBase[$identifier] = $this->attr($data);
    }

    /**
     * Creates a string of given key value pairs in form of html attributes,
     * if identifier is set, try to find corresponding basedata for
     * that element
     *
     * @param array  $data       of object to create
     * @param string $identifier key for the element in base data
     *
     * @return string created attributes
     */
    public function attr(array $data, string $identifier = '')
    {
        $element = '';

        if (!empty($identifier) && isset($this->elementBase[$identifier])) {
            $element .= $this->elementBase[$identifier];
        }

        foreach ($data as $attr => $value) {
            if (in_array($attr, $this->booleanAttributes) && empty($value)) {
                continue;
            }

            $str = $attr;
            if (!empty($value)) {
                $str .= '=' . '"' . $value . '"';
            }

            $element .= $str . ' ';
        }

        return $element;
    }
}
