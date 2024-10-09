<?php

/**
 * Model for AIPA records in Solr.
 *
 * PHP version 8
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
use Finna\RecordDriver\Feature\LrmiDriverTrait;

use function in_array;

/**
 * Model for AIPA records in Solr.
 *
 * @category VuFind
 * @package  RecordDrivers
 * @author   Aleksi Peebles <aleksi.peebles@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:record_drivers Wiki
 */
class SolrAipa extends SolrQdc implements ContainerFormatInterface
{
    use ContainerFormatTrait;
    use LrmiDriverTrait;

    /**
     * Encapsulated content type records.
     *
     * @var array
     */
    protected array $encapsulatedContentTypeRecords;

    /**
     * Array of excluded descriptions
     *
     * @var array
     */
    protected $excludedDescriptions = [];

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
        $cacheKey = __FUNCTION__ . "/$language" . ($includePdf ? '/1' : '/0');
        if (isset($this->cache[$cacheKey])) {
            return $this->cache[$cacheKey];
        }

        $xml = $this->getXmlRecord();
        $uniqueId = $this->getUniqueID();
        $result = [];
        $images = ['image/png', 'image/jpeg'];
        foreach ($xml->description as $desc) {
            $attr = $desc->attributes();
            $format = trim((string)($attr['format'] ?? ''));
            if ($format && in_array($format, $images)) {
                $url = (string)$desc;
                if ($this->isUrlLoadable($url, $uniqueId)) {
                    $result[] = [
                        'urls' => [
                            'small' => $url,
                            'medium' => $url,
                            'large' => $url,
                        ],
                        'description' => '',
                        'rights' => [],
                        'downloadable' => false,
                    ];
                }
            }
        }

        return $this->cache[$cacheKey] = $result;
    }

    /**
     * Get all subject headings associated with this record. Each heading is
     * returned as an array of chunks, increasing from least specific to most
     * specific.
     *
     * @param bool $extended Whether to return a keyed array with the following
     * keys:
     * - heading: the actual subject heading chunks
     * - type: heading type
     * - source: source vocabulary
     *
     * @return array
     */
    public function getAllSubjectHeadings($extended = false)
    {
        $lang = $this->getLocale();
        $lang = $lang === 'en-gb' ? 'en' : $lang;
        $xml = $this->getXmlRecord();
        $headings = [];
        foreach ($xml->subject as $heading) {
            $subjectLang = $heading->attributes()->{'lang'} ?? null;
            if ($subjectLang && $lang !== (string)$subjectLang) {
                continue;
            }
            $headings[] = (string)$heading;
        }

        $callback = function ($i) use ($extended) {
            return $extended
                ? ['heading' => [$i], 'type' => '', 'source' => '']
                : [$i];
        };
        return array_map($callback, array_unique($headings));
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
     * Return rights coverage for the record.
     *
     * @return string
     */
    public function getRightsCoverage(): string
    {
        return (string)($this->getXmlRecord()->rightsCoverage ?? '');
    }

    /**
     * Get general notes on the record.
     *
     * @return array
     */
    public function getGeneralNotes()
    {
        return [];
    }

    /**
     * Return record type.
     *
     * @return string
     */
    public function getType(): string
    {
        return (string)($this->getXmlRecord()->type ?? '');
    }

    /**
     * Get topics
     *
     * @param string $type defaults to /onto/yso/
     *
     * @return array
     */
    public function getTopics(string $type = '/onto/yso/'): array
    {
        return $this->getAllSubjectHeadings();
    }

    /**
     * Return encapsulated content type records.
     *
     * @return array Array of encapsulated content type records keyed by unique ID
     */
    public function getEncapsulatedContentTypeRecords(): array
    {
        if (!isset($this->encapsulatedContentTypeRecords)) {
            $this->encapsulatedContentTypeRecords = [];
            foreach ($this->getEncapsulatedRecords() as $encapsulatedRecord) {
                if ($encapsulatedRecord->getType() === 'content') {
                    $this->encapsulatedContentTypeRecords[$encapsulatedRecord->getUniqueId()]
                        = $encapsulatedRecord;
                }
            }
        }
        return $this->encapsulatedContentTypeRecords;
    }

    /**
     * Return full record as a filtered SimpleXMLElement for public APIs.
     *
     * @return \SimpleXMLElement
     */
    public function getFilteredXMLElement(): \SimpleXMLElement
    {
        $record = parent::getFilteredXMLElement();
        $filterFields = ['abstract', 'description'];
        foreach ($filterFields as $filterField) {
            while ($record->{$filterField}) {
                unset($record->{$filterField}[0]);
            }
        }
        return $this->filterEncapsulatedRecords($record);
    }

    /**
     * Return record driver instance for an encapsulated LRMI record.
     *
     * @param \SimpleXMLElement $item AIPA item XML
     *
     * @return AipaLrmi
     *
     * @see ContainerFormatTrait::getEncapsulatedRecordDriver()
     */
    protected function getLrmiDriver(\SimpleXMLElement $item): AipaLrmi
    {
        /* @var AipaLrmi $driver */
        $driver = $this->recordDriverManager->get('AipaLrmi');

        $driver->setContainerRecord($this);

        $data = [
            'id' => $this->getUniqueID()
                . ContainerFormatInterface::ENCAPSULATED_RECORD_ID_SEPARATOR
                . (string)$item->id,
            'title' => (string)$item->title,
            'fullrecord' => $item->asXML(),
            'position' => (int)$item->position,
            'record_format' => 'lrmi',
            'datasource_str_mv' => $this->getDataSource(),
        ];

        // Facets
        foreach ($item->educationalAudience as $audience) {
            $data['educational_audience_str_mv'][]
                = (string)$audience->educationalRole;
        }
        $data['educational_level_str_mv'] = array_map(
            'strval',
            (array)($item->learningResource->educationalLevel ?? [])
        );
        $data['educational_aim_str_mv'] = array_map(
            'strval',
            (array)($item->learningResource->teaches ?? [])
        );
        foreach ($item->learningResource->educationalAlignment ?? [] as $alignment) {
            if ($subject = $alignment->educationalSubject ?? null) {
                $data['educational_subject_str_mv'][] = (string)$subject;
            }
        }

        foreach ($item->type as $type) {
            $data['educational_material_type_str_mv'][] = (string)$type;
        }

        $driver->setRawData($data);

        return $driver;
    }
}
