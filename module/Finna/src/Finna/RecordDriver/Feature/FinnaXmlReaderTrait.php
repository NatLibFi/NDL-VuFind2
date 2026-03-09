<?php

/**
 * Functions for reading XML records.
 *
 * PHP version 8
 *
 * Copyright (C) The National Library of Finland 2018-2026.
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
 * @package  RecordDrivers
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:record_drivers Wiki
 */

namespace Finna\RecordDriver\Feature;

use FinnaXml\XmlDoc;

/**
 * Functions for reading XML records.
 *
 * Assumption: raw XML data can be found in $this->fields['fullrecord'].
 *
 * @category VuFind
 * @package  RecordDrivers
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:record_drivers Wiki
 */
trait FinnaXmlReaderTrait
{
    /**
     * The XML namespace.
     *
     * @var string
     */
    protected $xmlNs = 'http://www.w3.org/2000/xmlns/';

    /**
     * XML record. Access only via getXmlRecord() as this is initialized lazily.
     *
     * @var \SimpleXMLElement
     */
    protected $lazyXmlRecord = null;

    /**
     * XmlDoc. Access only via getXmlDoc() as this is initialized lazily.
     *
     * @var XmlDoc
     */
    protected ?XmlDoc $lazyXmlDoc = null;

    /**
     * Get access to the raw SimpleXMLElement object.
     *
     * @return \SimpleXMLElement
     */
    public function getXmlRecord()
    {
        if (null === $this->lazyXmlRecord) {
            $this->lazyXmlRecord
                = simplexml_load_string($this->fields['fullrecord']);
            if (false === $this->lazyXmlRecord) {
                throw new \Exception('Cannot Process XML Record');
            }
        }
        return $this->lazyXmlRecord;
    }

    /**
     * Get XmlDoc from fullrecord.
     *
     * @return XmlDoc
     */
    public function getXmlDoc(): XmlDoc
    {
        if (null === $this->lazyXmlDoc) {
            $this->lazyXmlDoc = (new XmlDoc())->parse($this->fields['fullrecord']);
        }
        return $this->lazyXmlDoc;
    }

    /**
     * Get XmlDoc from fullrecord.
     *
     * This is a default implementation that can be overridden in classes using this trait.
     *
     * @return XmlDoc
     */
    public function getXmlReader(): XmlDoc
    {
        return $this->getXmlDoc();
    }

    /**
     * Get lang attribute from xml namespace with fallback to default namespace.
     *
     * @param array $node XmlDoc node
     *
     * @return ?string
     */
    protected function getLangAttr(array $node): ?string
    {
        $xml = $this->getXmlReader();
        return $xml->attr($node, '{{$this->xmlNs}}lang') ?? $xml->attr($node, 'lang');
    }
}
