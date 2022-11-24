<?php
/**
 * Model for AIPA LRMI records.
 *
 * PHP version 7
 *
 * Copyright (C) The National Library of Finland 2022.
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
 * @package  RecordDrivers
 * @author   Aleksi Peebles <aleksi.peebles@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:record_drivers Wiki
 */
namespace Finna\RecordDriver;

use Finna\RecordDriver\Feature\ContainerFormatInterface;
use Finna\RecordDriver\Feature\ContainerFormatTrait;

/**
 * Model for AIPA LRMI records.
 *
 * @category VuFind
 * @package  RecordDrivers
 * @author   Aleksi Peebles <aleksi.peebles@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:record_drivers Wiki
 */
class AipaLrmi extends SolrLrmi implements ContainerFormatInterface
{
    use ContainerFormatTrait;

    /**
     * Constructor
     *
     * @param \Laminas\Config\Config $mainConfig     VuFind main configuration (omit
     * for built-in defaults)
     * @param \Laminas\Config\Config $recordConfig   Record-specific configuration
     * file (omit to use $mainConfig as $recordConfig)
     * @param \Laminas\Config\Config $searchSettings Search-specific configuration
     * file
     */
    public function __construct(
        $mainConfig = null,
        $recordConfig = null,
        $searchSettings = null
    ) {
        parent::__construct($mainConfig, $recordConfig, $searchSettings);

        // Set correct AIPA LRMI record XML element names for ContainerFormatTrait
        $this->encapsulatedRecordElementNames['item'] = 'material';
        $this->encapsulatedRecordElementNames['id'] = 'identifier';
        $this->encapsulatedRecordDefaultFormat = 'Curatedrecord';
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
     * @param string $language   Language for copyright information
     * @param bool   $includePdf Whether to include first PDF file when no image
     * links are found
     *
     * @return mixed
     */
    public function getAllImages($language = 'fi', $includePdf = false)
    {
        // AIPA LRMI records do not directly contain PDF files
        return parent::getAllImages($language, false);
    }

    /**
     * Return record driver instance for an encapsulated curated record.
     *
     * @param \SimpleXMLElement $item Curated record item XML
     *
     * @return CuratedRecord
     */
    protected function getCuratedrecordDriver(\SimpleXMLElement $item): CuratedRecord
    {
        $driver = $this->driverManager->get('CuratedRecord');

        $encapsulatedRecord = $this->recordLoader->load(
            (string)$item->identifier,
            DEFAULT_SEARCH_BACKEND,
            true
        );

        $data = [
            'id' => (string)$item->identifier,
            'record' => $encapsulatedRecord,
            'title' => $encapsulatedRecord->getTitle(),
            'position' => (int)$item->position,
            'notes' => (string)$item->comment,
        ];

        $driver->setRawData($data);

        return $driver;
    }
}
