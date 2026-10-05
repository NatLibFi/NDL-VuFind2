<?php

/**
 * Additional functionality for SolrForward and SolrForwardAuth records.
 *
 * PHP version 8
 *
 * Copyright (C) The National Library 2019.
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
 * @author   Samuli Sillanpää <samuli.sillanpaa@helsinki.fi>
 * @author   Konsta Raunio <konsta.raunio@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:record_drivers Wiki
 */

namespace Finna\RecordDriver\Feature;

use VuFindXml\XmlDoc;

/**
 * Additional functionality for SolrForward and SolrForwardAuth records.
 *
 * @category VuFind
 * @package  RecordDrivers
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @author   Samuli Sillanpää <samuli.sillanpaa@helsinki.fi>
 * @author   Konsta Raunio <konsta.raunio@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:record_drivers Wiki
 */
trait SolrForwardTrait
{
    /**
     * Forward XML namespace.
     *
     * @var string
     */
    protected $forwardNs = 'http://project-forward.eu/schemas/EN15907-forward';

    /**
     * Record metadata as an XmlDoc.
     *
     * @var XmlDoc
     */
    protected $lazyRecordXmlDoc;

    /**
     * Set raw data to initialize the object.
     *
     * @param mixed $data Raw data representing the record; Record Model
     * objects are normally constructed by Record Driver objects using data
     * passed in from a Search Results object. The exact nature of the data may
     * vary depending on the data source -- the important thing is that the
     * Record Driver + Search Results objects work together correctly.
     *
     * @return void
     */
    public function setRawData($data)
    {
        parent::setRawData($data);
        $this->lazyRecordXmlDoc = null;
    }

    /**
     * Return an array of image URLs associated with this record with keys:
     * - url         Image URL
     * - description Description text
     * - rights      Rights
     *   - copyright   Copyright (e.g. 'CC BY 4.0') (optional)
     *   - description Human readable description (array)
     *   - link        Link to copyright info
     *
     * @param bool $includePdf Whether to include first PDF file when no image
     *                         links are found
     *
     * @return array
     */
    public function getAllImages($includePdf = false)
    {
        $images = [];
        if (isset($this->cache[__FUNCTION__])) {
            return $this->cache[__FUNCTION__];
        }
        $xmlDoc = $this->getAllRecordsXmlDoc();
        foreach ($xmlDoc->all() as $xml) {
            foreach ($xmlDoc->all($xml, 'ProductionEvent') as $event) {
                $eventType = $xmlDoc->first($event, 'ProductionEventType');
                if (!($url = $xmlDoc->attr($eventType, 'elokuva-elonet-materiaali-kuva-url'))) {
                    continue;
                }
                if (!$this->isUrlLoadable($url, $this->getUniqueID())) {
                    continue;
                }

                if ($partValue = $xmlDoc->first($xml, 'Title/PartDesignation/Value')) {
                    $desc = $xmlDoc->attr($partValue, 'kuva-kuvateksti');
                } else {
                    $desc = '';
                }
                $rights = [];
                if ($copyright = $xmlDoc->attr($eventType, 'finna-kayttooikeus')) {
                    $rights['copyright'] = $copyright;
                    $link = $this->getRightsLink($rights['copyright']);
                    if ($link) {
                        $rights['link'] = $link;
                    }
                }
                if (!$this->maxAmountOfImages()) {
                    $image = [
                        'urls' => [
                            'small' => $url,
                            'medium' => $url,
                            'large' => $url,
                        ],
                        'description' => $desc,
                        'rights' => $rights,
                    ];
                    $image['downloadable'] = $this->allowRecordImageDownload($image);
                    $images[] = $image;
                }
                $this->imagesCount++;
            }
        }
        $this->cache[__FUNCTION__] = $images;
        return $images;
    }

    /**
     * Get all original records as an XmlDoc object.
     *
     * @return XmlDoc
     */
    protected function getAllRecordsXmlDoc(): XmlDoc
    {
        if ($this->lazyRecordXmlDoc === null) {
            $this->lazyRecordXmlDoc = new XmlDoc();
            $this->lazyRecordXmlDoc->parse($this->fields['fullrecord']);
            $this->lazyRecordXmlDoc->setDefaultNamespace($this->forwardNs);
        }
        return $this->lazyRecordXmlDoc;
    }

    /**
     * Get the original main record as an XmlDoc node.
     *
     * This is just a very simple wrapper to account for any future needs.
     *
     * @param XmlDoc $xml Document
     *
     * @return array
     */
    protected function getMainRecordNode(XmlDoc $xml): array
    {
        return $xml->first();
    }
}
