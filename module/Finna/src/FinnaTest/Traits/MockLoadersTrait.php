<?php

/**
 * Trait which returns pre-configured mocks
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
 * @package  Tests
 * @author   Juha Luoma <juha.luoma@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org Main Page
 */

namespace FinnaTest\Traits;

use Finna\File\Loader as FileLoader;
use Finna\Record\Loader;
use PHPUnit\Framework\MockObject\MockObject;
use VuFind\RecordDriver\Missing;

use function in_array;

/**
 * Trait which returns pre-configured mocks
 *
 * @category VuFind
 * @package  Tests
 * @author   Juha Luoma <juha.luoma@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org Main Page
 */
trait MockLoadersTrait
{
    /**
     * Get an instance of a very basic record loader, with load methods mocked
     *
     * @param array $records Array containing data for each record
     *                       [
     *                       'type' => Class for the record from path::class,
     *                       'fixture' => Path of the fixture to load or omit for none,
     *                       'raw_data' => Raw data for the record i.e index fields
     *                       ];
     *
     * @return MockObject
     */
    public function getFinnaRecordLoader(array $records = []): MockObject
    {
        $createdRecords = [];
        foreach ($records as $record) {
            if (!str_starts_with($record['type'], '\\')) {
                $record['type'] = '\\' . $record['type'];
            }
            $obj = new $record['type']();
            $fixture = $record['fixture'] ?? false ? $this->getFixture($record['fixture'], 'Finna') : '';
            $rawData = $record['raw_data'] ?? [];
            if ($fixture) {
                $rawData['fullrecord'] = $fixture;
            }
            $obj->setRawData($rawData);
            $createdRecords[] = $obj;
        }

        $methods = [
        'load' => function ($id, $source, $tolerateMissing = false, $params = null) use ($createdRecords) {
            foreach ($createdRecords as $record) {
                if ($record->getUniqueID() === $id && $record->getSourceIdentifier() === $source) {
                    return $record;
                }
                if ($tolerateMissing) {
                    return new Missing();
                }
                throw new \Exception('Record not found');
            }
        },
        ];
        return $this->getMockedObject(Loader::class, $methods);
    }

    /**
     * Get Finna file loader as mocked object
     *
     * @param array $urls Urls to be tested
     *
     * @return MockObject
     */
    public function getFinnaFileLoader(array $urls = []): MockObject
    {
        $methods = [
        'proxyFileLoad' => function ($url, $fileName, $format) use ($urls) {
            return in_array($url, $urls);
        },
        ];
        return $this->getMockedObject(FileLoader::class, $methods);
    }
}
