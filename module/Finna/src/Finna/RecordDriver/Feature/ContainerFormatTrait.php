<?php

/**
 * Common functionality for container record formats.
 *
 * PHP version 8
 *
 * Copyright (C) The National Library of Finland 2022-2025.
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
 * @author   Aleksi Peebles <aleksi.peebles@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:record_drivers Wiki
 */

namespace Finna\RecordDriver\Feature;

use Finna\Record\Loader;
use Finna\RecordDriver\CuratedRecord;
use Finna\RecordDriver\CuratedRecordList;
use Finna\RecordDriver\PluginManager;
use FinnaXml\XmlDoc;
use VuFind\RecordDriver\AbstractBase;
use VuFindSearch\ParamBag;
use VuFindSearch\Response\RecordInterface;

use function count;
use function is_callable;

/**
 * Common functionality for container record formats.
 *
 * @category VuFind
 * @package  RecordDrivers
 * @author   Aleksi Peebles <aleksi.peebles@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:record_drivers Wiki
 */
trait ContainerFormatTrait
{
    /**
     * Aipa XML namespace.
     *
     * @var string
     */
    protected string $aipaNs = 'http://finna.fi/ns/aipa/';

    /**
     * LRMI XML namespace.
     *
     * @var string
     */
    protected string $lrmiNs = 'http://dublincore.org/dcx/lrmi-terms/1.1/';

    /**
     * Cache for encapsulated records.
     *
     * @var array
     */
    protected array $encapsulatedRecordCache;

    /**
     * Record driver plugin manager.
     *
     * @var PluginManager
     */
    protected PluginManager $recordDriverManager;

    /**
     * Record loader.
     *
     * @var Loader
     */
    protected Loader $recordLoader;

    /**
     * Attach record driver plugin manager.
     *
     * @param PluginManager $recordDriverManager Record driver plugin manager
     *
     * @return void
     */
    public function attachRecordDriverManager(
        PluginManager $recordDriverManager
    ): void {
        $this->recordDriverManager = $recordDriverManager;
    }

    /**
     * Attach record loader.
     *
     * @param Loader $recordLoader Record loader
     *
     * @return void
     */
    public function attachRecordLoader(Loader $recordLoader): void
    {
        $this->recordLoader = $recordLoader;
    }

    /**
     * Get records encapsulated in this container record.
     *
     * @param int  $offset Offset for results
     * @param ?int $limit  Limit for results (null for none)
     *
     * @return AbstractBase[]
     * @throws \RuntimeException If the format of an encapsulated record is not
     * supported
     */
    public function getEncapsulatedRecords(
        int $offset = 0,
        ?int $limit = null
    ): array {
        if (null !== $limit) {
            $limit += $offset;
        }
        $cache = $this->getEncapsulatedRecordCache();
        $results = [];
        for ($p = $offset; null === $limit || $p < $limit; $p++) {
            if (!isset($cache[$p])) {
                // Reached end of records
                break;
            }
            $results[] = $this->getCachedEncapsulatedRecordDriver($p);
        }
        $this->loadNeededRecords($results);
        return $results;
    }

    /**
     * Returns the requested encapsulated record or null if not found.
     *
     * @param string $id Encapsulated record ID
     *
     * @return ?RecordInterface
     * @throws \RuntimeException If the format is not supported
     */
    public function getEncapsulatedRecord(string $id): ?RecordInterface
    {
        $cache = $this->getEncapsulatedRecordCache();
        foreach ($cache as $position => $record) {
            if ($id === $record['id']) {
                $driver = $this->getCachedEncapsulatedRecordDriver($position);
                if (
                    $driver instanceof EncapsulatedRecordInterface
                    && $needed = $driver->needsRecordLoaded()
                ) {
                    $loadedRecord = $this->recordLoader->load(
                        $needed['id'],
                        $needed['source'],
                        true,
                        new ParamBag(['finna.ignore_source_filter' => 1])
                    );
                    $driver->setLoadedRecord($loadedRecord);
                }
                return $driver;
            }
        }
        return null;
    }

    /**
     * Returns the total number of encapsulated records.
     *
     * @return int
     */
    public function getEncapsulatedRecordTotal(): int
    {
        return count($this->getEncapsulatedRecordCache());
    }

    /**
     * Returns the tag name of XML elements containing an encapsulated record.
     *
     * @return string
     */
    protected function getEncapsulatedRecordElementTagName(): string
    {
        return "{{$this->aipaNs}}item";
    }

    /**
     * Return all encapsulated record items.
     *
     * @return array
     */
    protected function getEncapsulatedRecordItems(): array
    {
        // Implementation for XML items
        $items = [];
        $xml = $this->getXmlReader();
        $tagName = $this->getEncapsulatedRecordElementTagName();
        foreach ($xml->all(path: $tagName) as $node) {
            $item = new XmlDoc();
            $item->import($xml->export($node));
            $items[] = $item;
        }
        return $items;
    }

    /**
     * Return format for an encapsulated record.
     *
     * @param mixed $item Encapsulated record item
     *
     * @return string
     * @throws \RuntimeException If the format can not be determined
     */
    protected function getEncapsulatedRecordFormat($item): string
    {
        // Implementation for XmlDoc items with format specified in a 'format' attribute
        if (null !== ($format = $item->attr($item->root(), 'format'))) {
            return ucfirst(strtolower((string)$format));
        }
        throw new \RuntimeException('Unable to determine format');
    }

    /**
     * Return position for an encapsulated record, or null for unspecified position.
     *
     * @param mixed $item Encapsulated record item
     *
     * @return int|null
     */
    protected function getEncapsulatedRecordPosition($item): ?int
    {
        // Implementation for XmlDoc items with position optionally specified in a
        // 'position' attribute or element
        $position = $item->attr($item->root(), "{{$this->aipaNs}}position")
            ?? $item->firstValue(path: "{{$this->lrmiNs}}position")
            ?? null;
        return null !== $position
            ? (int)$position
            : null;
    }

    /**
     * Return encapsulated record view type.
     *
     * @return string
     */
    public function getEncapsulatedRecordViewType(): string
    {
        // Implementation for XML records with view type optionally specified in a
        // 'display' attribute or element
        $xml = $this->getXmlRecord();
        $display = $xml->attributes()->{'display'}
            ?? $xml->display
            ?? 'grid';
        return (string)($display);
    }

    /**
     * Return record driver for an encapsulated record.
     *
     * @param mixed $item Encapsulated record item
     *
     * @return ?AbstractBase
     * @throws \RuntimeException If the format is not supported
     */
    protected function getEncapsulatedRecordDriver($item): ?AbstractBase
    {
        $format = $this->getEncapsulatedRecordFormat($item);
        $method = "get{$format}Driver";
        if (!is_callable([$this, $method])) {
            throw new \RuntimeException('No driver for format ' . $format);
        }
        return $this->$method($item);
    }

    /**
     * Get cache containing all encapsulated records.
     *
     * The cache is an array of arrays with the following keys:
     * - id: Record ID
     * - item: Record item
     * - driver: VuFind record driver
     *
     * @return array
     */
    protected function getEncapsulatedRecordCache(): array
    {
        if (isset($this->encapsulatedRecordCache)) {
            return $this->encapsulatedRecordCache;
        }

        $records = [];
        foreach ($this->getEncapsulatedRecordItems() as $item) {
            $driver = $this->getEncapsulatedRecordDriver($item);
            $record = [
                'id' => $driver->getUniqueId(),
                'item' => $item,
                'driver' => $driver,
            ];
            // Position is optional
            if (null !== ($position = $this->getEncapsulatedRecordPosition($item))) {
                $records[$position] = $record;
            } else {
                $records[] = $record;
            }
        }
        // Sort by key in ascending order
        ksort($records);
        // Ensure that keys start from 0 and are sequential
        $records = array_values($records);

        $this->encapsulatedRecordCache = $records;
        return $records;
    }

    /**
     * Return record driver for an encapsulated record in the provided position or
     * null if the position is not valid.
     *
     * @param int $position Record position
     *
     * @return ?AbstractBase
     * @throws \RuntimeException If the format is not supported
     */
    protected function getCachedEncapsulatedRecordDriver(
        int $position
    ): ?EncapsulatedRecordInterface {
        // Ensure cache is warm
        $cache = $this->getEncapsulatedRecordCache();
        return $cache[$position]['driver'] ?? null;
    }

    /**
     * Loads any records needed by encapsulated record drivers to be loaded.
     *
     * @param array $records Record drivers
     *
     * @return void
     */
    protected function loadNeededRecords(array $records): void
    {
        // Maps records that need to be loaded to the provided record drivers in case
        // multiple provided record drivers need the same record to be loaded.
        $neededMap = [];
        // Deduplicated list of records that need to be loaded so that the same
        // record won't be loaded more than once.
        $ids = [];
        foreach ($records as $i => $record) {
            if (
                $record instanceof EncapsulatedRecordInterface
                && $needed = $record->needsRecordLoaded()
            ) {
                $source = $needed['source'];
                if (!isset($neededMap[$source][$needed['id']])) {
                    $neededMap[$source][$needed['id']] = [];
                }
                $neededMap[$source][$needed['id']][] = $i;
                $alreadyAdded = false;
                foreach ($ids as $existing) {
                    if ($existing === $needed) {
                        $alreadyAdded = true;
                        break;
                    }
                }
                if (!$alreadyAdded) {
                    $ids[] = $needed;
                }
            }
        }
        if (!empty($ids)) {
            // Load needed records and call the setLoadedRecord() method of the
            // respective record driver in the provided array.
            $loadedRecords = $this->recordLoader->loadBatchIgnoringSourceFilter($ids);
            foreach ($loadedRecords as $loadedRecord) {
                $loadedSource = $loadedRecord->getSourceIdentifier();
                $loadedId = $loadedRecord->getUniqueID();
                foreach ($neededMap[$loadedSource][$loadedId] ?? [] as $i) {
                    $records[$i]->setLoadedRecord($loadedRecord);
                }
                if ($previousId = $loadedRecord->tryMethod('getPreviousUniqueID')) {
                    foreach ($neededMap[$loadedSource][$previousId] ?? [] as $i) {
                        $records[$i]->setLoadedRecord($loadedRecord);
                    }
                }
            }
        }
    }

    /**
     * Filter encapsulated records of this format for public APIs.
     *
     * @param XmlDoc $record Container record XML.
     *
     * @return XmlDoc
     */
    protected function filterEncapsulatedRecords(XmlDoc $record): XmlDoc
    {
        // Update encapsulated record data with their filtered documents:
        $encapsulatedTagLocalName = $record->localName($this->getEncapsulatedRecordElementTagName());
        $record->modify(
            function (&$node) use ($record, $encapsulatedTagLocalName): void {
                if ($record->localName($node) === $encapsulatedTagLocalName) {
                    $childDoc = new XmlDoc();
                    $childDoc->import($record->export($node));
                    $encapsulatedRecord = $this->getEncapsulatedRecord(
                        $this->getEncapsulatedRecordDriver($childDoc)->getUniqueID()
                    );
                    if (is_callable([$encapsulatedRecord, 'getFilteredXMLElement'])) {
                        $filtered = $encapsulatedRecord->getFilteredXMLElement();
                        $record->replaceChildren($node, $filtered);
                    }
                }
            }
        );
        return $record;
    }

    /**
     * Return full record as a filtered XmlDoc for public APIs.
     *
     * @return XmlDoc
     */
    public function getFilteredXMLElement(): XmlDoc
    {
        $record = clone $this->getXmlReader();
        return $this->filterEncapsulatedRecords($record);
    }

    /**
     * Return full record as filtered XML for public APIs.
     *
     * @return string
     */
    public function getFilteredXML()
    {
        return $this->getFilteredXMLElement()->toXML();
    }

    /**
     * Return record driver instance for an encapsulated curated record.
     *
     * @param XmlDoc $item Curated record item XML
     *
     * @return CuratedRecord
     *
     * @see ContainerFormatTrait::getEncapsulatedRecordDriver()
     */
    protected function getCuratedRecordDriver(XmlDoc $item): CuratedRecord
    {
        /* @var CuratedRecord $driver */
        $driver = $this->recordDriverManager->get('CuratedRecord');

        $driver->setContainerRecord($this);

        $data = [
            'id' => $item->firstValue(path: "{{$this->aipaNs}}identifier"),
            'notes' => $item->firstValue(path: "{{$this->aipaNs}}comment"),
            'fullrecord' => $item->toXML(),
        ];

        $driver->setRawData($data);

        return $driver;
    }

    /**
     * Return record driver instance for an encapsulated curated record list.
     *
     * @param XmlDoc $item Curated record list item XML
     *
     * @return CuratedRecordList
     *
     * @see ContainerFormatTrait::getEncapsulatedRecordDriver()
     */
    protected function getCuratedRecordListDriver(XmlDoc $item): CuratedRecordList
    {
        /* @var CuratedRecordList $driver */
        $driver = $this->recordDriverManager->get('CuratedRecordList');

        $driver->setContainerRecord($this);

        $data = [
            'id' => $this->getUniqueID()
                . ContainerFormatInterface::ENCAPSULATED_RECORD_ID_SEPARATOR
                . $item->firstValue(path: "{{$this->aipaNs}}identifier"),
            'title' => $item->firstValue(path: "{{$this->aipaNs}}name"),
            'description' => $item->firstValue(path: "{{$this->aipaNs}}description"),
            'additionalType' => $item->firstValue(path: "{{$this->aipaNs}}additionalType"),
            'fullrecord' => $item->toXML(),
        ];

        $driver->setRawData($data);

        return $driver;
    }
}
