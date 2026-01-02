<?php

/**
 * XML parser
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
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 * @category VuFind
 * @package  Record
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development Wiki
 */

declare(strict_types=1);

namespace Finna\Record\XML;

/**
 * XML parser
 *
 * This is a light-weight XML parser inspired by sabre-xml, but does not produce compatible results.
 *
 * @category VuFind
 * @package  Record
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development Wiki
 */
class XmlParser extends \XMLReader
{
    /**
     * Parse XML into an associative array.
     *
     * Returns an associative array with the following elements:
     *   name  - Element name
     *   val   - Values (text content)
     *   sub   - Child nodes
     *   attrs - Attributes
     *
     * @param string $xml XML string
     *
     * @return array<string, mixed>
     *
     * @throws \RuntimeException
     */
    public function parse(string $xml): array
    {
        $previousInternalErrors = libxml_use_internal_errors(true);
        try {
            $this->XML($xml);

            while (self::ELEMENT !== $this->nodeType) {
                $this->read();
            }
            $result = $this->processElement();
        } finally {
            libxml_use_internal_errors($previousInternalErrors);
        }

        return $result;
    }

    /**
     * Move to next node in document.
     *
     * Throws an exception on failure.
     *
     * @return bool
     *
     * @throws \RuntimeException
     */
    public function read(): bool
    {
        if (!parent::read()) {
            $errors = libxml_get_errors();
            libxml_clear_errors();
            if ($errors) {
                throw new \RuntimeException($this->errorsToString($errors));
            }
        }
        return true;
    }

    /**
     * Process the current XML element.
     *
     * @return array <string, mixed>
     */
    public function processElement(): array
    {
        $result = [
            'name' => $this->getClark(),
            'val' => '',
            'sub' => [],
            'attrs' => $this->getAttributes(),
        ];

        if (self::ELEMENT === $this->nodeType && $this->isEmptyElement) {
            $this->next();
            return $result;
        } else {
            $this->read();
            while (true) {
                switch ($this->nodeType) {
                    case self::ELEMENT:
                        $result['sub'][] = $this->processElement();
                        break;
                    case self::TEXT:
                    case self::CDATA:
                        $result['val'] .= $this->value;
                        $this->read();
                        break;
                    case self::END_ELEMENT:
                        $this->read();
                        return $result;
                    case self::NONE:
                        throw new \Exception('Unexpected XML parsing state');
                    default:
                        $this->read();
                        break;
                }
            }
        }
    }

    /**
     * Get all attributes from current element.
     *
     * @return array<string, string>
     */
    public function getAttributes(): array
    {
        if (!$this->hasAttributes) {
            return [];
        }

        $attributes = [];
        while ($this->moveToNextAttribute()) {
            if ($this->namespaceURI) {
                if ('http://www.w3.org/2000/xmlns/' === $this->namespaceURI) {
                    continue;
                }
                $attributes[$this->getClark()] = $this->value;
            } else {
                $attributes[$this->localName] = $this->value;
            }
        }
        $this->moveToElement();

        return $attributes;
    }

    /**
     * Get current nodename in clark notation.
     *
     * @return ?string
     */
    public function getClark(): ?string
    {
        return $this->localName ? ('{' . $this->namespaceURI . '}' . $this->localName) : null;
    }

    /**
     * Convert LibXML errors to a string.
     *
     * @param array $errors LibXML errors
     *
     * @return string
     */
    protected function errorsToString(array $errors): string
    {
        return "XML error '{$errors[0]->message}' at {$errors[0]->line}:{$errors[0]->column}";
    }
}
