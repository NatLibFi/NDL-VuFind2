<?php
/**
 * Model for AIPA LRMI records.
 *
 * PHP version 7
 *
 * Copyright (C) The National Library of Finland 2022-2023.
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
     * Get an array of formats/extents for the record
     *
     * @return array
     */
    public function getPhysicalDescriptions(): array
    {
        return [];
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
        // AIPA LRMI records do not directly contain PDF files.
        return parent::getAllImages($language, false);
    }

    /**
     * Return type of access restriction for the record.
     *
     * @param string $language Language
     *
     * @return mixed array with keys:
     *   'copyright'   Copyright (e.g. 'CC BY 4.0')
     *   'link'        Link to copyright info, see IndexRecord::getRightsLink
     *   or false if no access restriction type is defined.
     */
    public function getAccessRestrictionsType($language)
    {
        $xml = $this->getXmlRecord();
        $rights = [];
        if (!empty($xml->rights)) {
            $rights['copyright'] = $this->getMappedRights((string)$xml->rights);
            if ($link = $this->getRightsLink($rights['copyright'], $language)) {
                $rights['link'] = $link;
            }
            return $rights;
        }
        return false;
    }

    /**
     * Return study objectives, or null if not found in record.
     *
     * @return ?string
     */
    public function getStudyObjectives(): ?string
    {
        $studyObjectives = null;
        $xml = $this->getXmlRecord();
        foreach ($xml->learningResource as $learningResource) {
            if ($learningResource->studyObjectives) {
                if (null === $studyObjectives) {
                    $studyObjectives = '';
                }
                $studyObjectives .= (string)$learningResource->studyObjectives;
            }
        }
        return $studyObjectives;
    }

    /**
     * Return assignment ideas, or null if not found in record.
     *
     * @return ?string
     */
    public function getAssignmentIdeas(): ?string
    {
        $xml = $this->getXmlRecord();
        if ($xml->assignmentIdeas) {
            return (string)$xml->assignmentIdeas;
        }
        return null;
    }

    /**
     * Return all encapsulated record items.
     *
     * @return array
     */
    protected function getEncapsulatedRecordItems(): array
    {
        // Implementation for XML items in 'material' elements.
        $items = [];
        $xml = $this->getXmlRecord();
        foreach ($xml->material as $item) {
            $items[] = $item;
        }
        return $items;
    }

    /**
     * Return ID for an encapsulated record.
     *
     * @param mixed $item Encapsulated record item.
     *
     * @return string
     */
    protected function getEncapsulatedRecordId($item): string
    {
        // Implementation for XML items with ID specified in an 'identifier' element
        return (string)$item->identifier;
    }

    /**
     * Return format for an encapsulated record.
     *
     * @param mixed $item Encapsulated record item
     *
     * @return string
     */
    protected function getEncapsulatedRecordFormat($item): string
    {
        return 'Curatedrecord';
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
        $driver = $this->recordDriverManager->get('CuratedRecord');

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
