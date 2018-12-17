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
     * Boolean attributes
     *
     * @var array
     */
    protected $booleanAttributes = [
        'selected',
        'disabled',
        'checked',
        'open',
        'multiple'
    ];

    /**
     * Array holding base data for elements
     *
     * @var array
     */
    protected $elementBase = [];

    /**
     * \Zend\Escaper\Escaper
     *
     * @var Escaper
     */
    protected $escaper;

    /**
     * HtmlElement constructor
     */
    public function __construct()
    {
        $this->escaper = new \Zend\Escaper\Escaper('utf-8');
    }

    /**
     * Adds a base-element to $this->elementBase array
     * identified by $identifier
     *
     * @param string $identifier key for the element in base data
     * @param array  $data       attributes of the element
     *
     * @return void
     */
    public function addAttributeTemplate(string $identifier, array $data)
    {
        $this->elementBase[$identifier] = $this->escapeAttributes($data);
    }

    /**
     * Escapes given values from an array
     *
     * @param array $array with escapable data
     *
     * @return array escaped array
     */
    public function escapeAttributes(array $array)
    {
        $escaped = [];

        foreach ($array as $key => $value) {
            $escaped[$key] = $this->escaper->escapeHtmlAttr($value);
        }

        return $escaped;
    }

    /**
     * Creates a string of given key value pairs in form of html attributes,
     * if identifier is set, try to find corresponding basedata for
     * that element
     *
     * @param array  $data       attributes of element to create
     * @param string $identifier key for the element in base data
     *
     * @throws OutOfBoundsException if the given key is not set in elementBase array
     *
     * @return string created attributes
     */
    public function getAttributes(array $data, string $identifier = null)
    {
        if (isset($identifier)
            && !isset($this->elementBase[$identifier])
        ) {
            throw new \OutOfBoundsException("Element $identifier not defined.");
        }

        $hasBaseElement = isset($identifier)
            && isset($this->elementBase[$identifier]);

        $baseData = ($hasBaseElement) ? $this->elementBase[$identifier] : [];
        $newData = $this->escapeAttributes($data);

        if ($hasBaseElement) {
            $newData = $this->appendAttributes($baseData, $newData);
        }

        return $this->stringifyAttributes($newData);
    }

    /**
     * Stringify array element
     *
     * @param array $element to stringify
     *
     * @return string stringified version of the key values
     */
    protected function stringifyAttributes(array $element)
    {
        $stringified = [];
        foreach ($element as $key => $value) {
            $stringified[] = "$key=\"$value\"";
        }
        return implode(' ', $stringified);
    }

    /**
     * Function to add attributes to an existing element
     *
     * @param array $baseData base attributes of element
     * @param array $new      attributes for element
     *
     * @return array $combined array to return
     */
    protected function appendAttributes(
        array $baseData,
        array $new
    ) {
        $combined = $baseData;

        foreach ($new as $key => $value) {
            $isEmpty = (strlen($value) === 0);

            if (in_array($key, $this->booleanAttributes) && $isEmpty) {
                continue;
            }

            $combined[$key] = isset($combined[$key])
                ? $combined[$key] . " $value" : $value;
        }

        return $combined;
    }
}
