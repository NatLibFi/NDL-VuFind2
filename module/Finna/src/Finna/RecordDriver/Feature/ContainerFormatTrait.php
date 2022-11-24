<?php
/**
 * Common functionality for container record formats.
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
namespace Finna\RecordDriver\Feature;

use Finna\Record\Loader;
use Finna\RecordDriver\PluginManager;
use VuFind\RecordDriver\AbstractBase;

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
     * Names of specific encapsulated record XML elements.
     *
     * These may be changed in the class using the trait.
     *
     * @var string[]
     */
    protected array $encapsulatedRecordElementNames = [
        'item' => 'item',
        'id' => 'id',
        'position' => 'position',
        'format' => 'format',
    ];

    /**
     * Default format for encapsulated records that do not have a format element.
     *
     * This may be changed in the class using the trait.
     *
     * @var ?string
     */
    protected ?string $encapsulatedElementDefaultFormat = null;

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
    protected PluginManager $driverManager;

    /**
     * Record loader.
     *
     * @var Loader
     */
    protected Loader $recordLoader;

    /**
     * Attach record driver plugin manager.
     *
     * @param PluginManager $driverManager Record driver plugin manager
     *
     * @return void
     */
    public function attachDriverManager(PluginManager $driverManager): void
    {
        $this->driverManager = $driverManager;
    }

    /**
     * Attach record loader.
     *
     * @param Loader $recordLoader Record loader
     *
     * @return void
     */
    public function attachRecordLoader(Loader $recordLoader)
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
        return $results;
    }

    /**
     * Returns the requested encapsulated record or null if not found.
     *
     * @param string $id Encapsulated record ID
     *
     * @return ?AbstractBase
     * @throws \RuntimeException If the format is not supported
     */
    public function getEncapsulatedRecord(string $id): ?AbstractBase
    {
        $cache = $this->getEncapsulatedRecordCache();
        foreach ($cache as $position => $record) {
            if ($id === $record['id']) {
                return $this->getCachedEncapsulatedRecordDriver($position);
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
     * Return record driver for an encapsulated record.
     *
     * @param \SimpleXMLElement $item Encapsulated record XML
     *
     * @return ?AbstractBase
     * @throws \RuntimeException If the format is not supported
     */
    protected function getEncapsulatedRecordDriver(
        \SimpleXMLElement $item
    ): ?AbstractBase {
        $formatElement = $this->encapsulatedRecordElementNames['format'];
        if (isset($item->{$formatElement})) {
            $format = ucfirst(strtolower((string)$item->{$formatElement}));
        } elseif (isset($this->encapsulatedElementDefaultFormat)) {
            $format = $this->encapsulatedElementDefaultFormat;
        } else {
            throw new \RuntimeException('Unable to determine format');
        }
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
     * - item: Record item XML
     *
     * and if the driver has been loaded using
     * ContainerFormatTrait::getCachedEncapsulatedRecordDriver():
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
        $xml = $this->getXmlRecord();
        $itemElement = $this->encapsulatedRecordElementNames['item'];
        $idElement = $this->encapsulatedRecordElementNames['id'];
        $positionElement = $this->encapsulatedRecordElementNames['position'];
        foreach ($xml->$itemElement as $item) {
            $record = [
                'id' => (string)$item->{$idElement},
                'item' => $item,
            ];
            // Position element is optional
            if (isset($item->{$positionElement})) {
                $records[(int)$item->{$positionElement}] = $record;
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
    ): ?AbstractBase {
        // Ensure cache is warm
        $cache = $this->getEncapsulatedRecordCache();
        // Ensure position is valid
        if (!isset($cache[$position])) {
            return null;
        }
        // Try to get driver from cache
        if (!$driver = $cache[$position]['driver'] ?? null) {
            // Not in cache so get driver and add it to cache
            $driver
                = $this->encapsulatedRecordCache[$position]['driver']
                    = $this->getEncapsulatedRecordDriver($cache[$position]['item']);
        }
        return $driver;
    }
}
