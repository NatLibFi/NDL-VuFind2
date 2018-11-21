<?php
/**
 * HtmlElement helper
 *
 * PHP version 7
 *
 * Copyright (C) The National Library of Finland 2016.
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

    /**
     * Creates a string of given key value pairs in form of html attributes
     *
     * @param array $data of object to create
     *
     * @return string created attributes
     */
    public function attr(array $data)
    {
        $element = '';

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
