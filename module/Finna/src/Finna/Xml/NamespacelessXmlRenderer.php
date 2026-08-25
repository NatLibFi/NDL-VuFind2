<?php

/**
 * XML renderer that outputs non-namespaced XML for legacy purposes.
 *
 * PHP version 8
 *
 * Copyright (C) The National Library 2026.
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
 * @package  XML
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:record_drivers Wiki
 */

namespace Finna\Xml;

use VuFindXml\XmlRenderer;
use XMLWriter;

/**
 * XML renderer that outputs non-namespaced XML for legacy purposes.
 *
 * @category VuFind
 * @package  XML
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:record_drivers Wiki
 */
class NamespacelessXmlRenderer extends XmlRenderer
{
    /**
     * Render the parsed array as an XML string.
     *
     * This is almost the same as XmlRenderer's render method, but forces omitNamespacePrefixes to true.
     *
     * @param int    $indent           Indent (pretty-print) by $indent spaces
     * @param bool   $trim             Trim leading and trailing whitespace from text nodes?
     * @param ?array $node             Node to serialize (omit to serialize the full record)
     * @param bool   $omitSinglePrefix Omit namespace prefix if there's only a single namespace?
     *
     * @return string
     */
    public function render(
        int $indent = 0,
        bool $trim = false,
        ?array $node = null,
        bool $omitSinglePrefix = false
    ): string {
        $this->trim = $trim;
        // First go through all nodes and generate namespace prefixes as needed:
        $this->checkNode($node);
        $namespaces = $this->parsed['namespaces'];
        unset($namespaces['xsi']);
        $this->omitNamespacePrefixes = true;
        $this->writer = new XMLWriter();
        $this->writer->openMemory();
        $this->writer->setIndent((bool)$indent);
        $this->writer->setIndentString(str_repeat(' ', $indent));
        $this->writer->startDocument();
        $this->nodeToXML($node, root: true);
        $this->writer->endDocument();
        return $this->writer->flush();
    }
}
