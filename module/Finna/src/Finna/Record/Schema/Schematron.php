<?php

/**
 * ISO Schematron validator extensions.
 *
 * PHP version 8
 *
 * Copyright (C) The National Library of Finland 2025.
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
 * @package  Record
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org Main Page
 */

namespace Finna\Record\Schema;

use DOMElement;
use DOMNode;
use InvalidArgumentException;

/**
 * ISO Schematron validator extensions.
 *
 * @category VuFind
 * @package  Controller_Plugins
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org Main Page
 */
class Schematron extends Milo\Schematron
{
    /**
     * Constructor
     *
     * @param string $namespace Schema namespace (self::NS_*)
     *
     * @throws InvalidArgumentException when unsupported namespace passed
     */
    public function __construct($namespace = self::NS_DETECT)
    {
        static::$xPathClass = SchematronXPath::class;
        parent::__construct($namespace);
    }

    /**
     * Expands <sch:name> and <sch:value-of> in assertion/report message.
     *
     * @param DOMElement           $stmt    Statement
     * @param Milo\SchematronXPath $xPath   XPath
     * @param DOMNode              $current Current node
     * @param array                $lets    Rule's lets
     *
     * @return string
     */
    protected function statementToMessage(DOMElement $stmt, Milo\SchematronXPath $xPath, DOMNode $current, $lets = [])
    {
        $result = parent::statementToMessage($stmt, $xPath, $current, $lets);
        if ($role = $stmt->getAttribute('role')) {
            $result = "[$role] $result";
        }
        return $result;
    }
}
