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

use Finna\Cache\Manager;
use Finna\File\Loader as FileLoader;
use Finna\Record\Loader;
use FinnaSearch\Backend\Solr\Response\Json\RecordCollection;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use Laminas\Mvc\I18n\Translator;
use VuFind\Config\Config;
use VuFind\Http\GuzzleService;
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
     * Get Finna Record Loader.
     *
     * @param array $records Array containing data for each record
     *                       [
     *                          'type' => Class for the record from path::class,
     *                          'fixture' => Path of the fixture to load or omit for none,
     *                          'raw_data' => Raw data for the record i.e index fields
     *                       ];
     *
     * @return Loader
     */
    public function getFinnaRecordLoader(array $records = []): Loader
    {
        $searchService = $this->container->createMock(\VuFindSearch\Service::class, ['invoke']);
        $searchService->expects($this->any())->method('invoke')->willReturnCallback(function ($command) use ($records) {
            $backendIdentifier = $command->getTargetIdentifier();
            $recordIdentifier = $command->getRecordIdentifier();
            $foundRecords = [];
            foreach ($records as $record) {
                if (
                    $record['raw_data']['id'] === $recordIdentifier &&
                    $record['raw_data']['source'] === $backendIdentifier
                ) {
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
                    $foundRecords[] = $obj;
                }
            }
            $mockedCommand = $this->container->createMock(\VuFindSearch\Command\RetrieveCommand::class, ['getResult']);
            $recordCollection = $this->container->createMock(RecordCollection::class, ['getRecords']);
            $recordCollection->expects($this->any())->method('getRecords')->willReturn($foundRecords);
            $mockedCommand->expects($this->any())->method('getResult')->willReturn($recordCollection);
            return $mockedCommand;
        });

        // Use the real class for improving test coverage passively
        return new \Finna\Record\Loader(
            $searchService,
            $this->getRecordDriverPluginManager(),
        );
    }

    /**
     * Get record driver plugin manager
     *
     * @param array $config Main config
     *
     * @return \Finna\RecordDriver\PluginManager
     */
    public function getRecordDriverPluginManager(array $config = []): \Finna\RecordDriver\PluginManager
    {
        $configContainer = $this->container->createMock(\VuFind\Config\PluginManager::class, ['get']);
        $configMap = [
            ['config', null, new Config($config)],
        ];

        $dbTablePluginManager = $this->container->createMock(\VuFind\Db\Table\PluginManager::class);
        $dbServicePluginManager = $this->container->createMock(\VuFind\Db\Service\PluginManager::class);
        $translator = $this->container->createMock(Translator::class);

        $configContainer->expects($this->any())->method('get')->willReturnMap($configMap);
        // Create a mock container for factory
        $mockContainer = $this->container->createMock(\VuFind\Config\PluginManager::class);
        $serviceMap = [
            ['Missing', null, new Missing()],
            [\VuFind\Config\PluginManager::class, null, $configContainer],
            [\VuFind\Db\Table\PluginManager::class, null, $dbTablePluginManager],
            [\VuFind\Db\Service\PluginManager::class, null, $dbServicePluginManager],
            [Translator::class, null, $translator],
        ];
        $mockContainer->expects($this->any())->method('get')->willReturnMap($serviceMap);

        // Use the real class for improving test coverage passively
        return new \Finna\RecordDriver\PluginManager(
            $mockContainer,
            []
        );
    }

    /**
     * Get a Finna file loader object.
     *
     * @param array $urls Urls which results in response 200
     *
     * @return FileLoader
     */
    public function getFinnaFileLoader(array $urls = []): FileLoader
    {
        $mockedGuzzle = $this->container->createMock(GuzzleService::class, ['createClient']);
        $mockedGuzzleClient = $this->container->createMock(Client::class, ['request']);
        $mockedGuzzleClient->expects($this->any())->method('request')->willReturnCallback(
            function ($method, $uri, $options = []) use ($urls) {
                if (in_array($uri, $urls)) {
                    return new Response();
                }
                return new Response(404);
            }
        );
        $mockedGuzzle->expects($this->any())->method('createClient')->willReturn($mockedGuzzleClient);
        return new FileLoader(
            $this->container->createMock(Manager::class),
            new \VuFind\Config\Config([]),
            $mockedGuzzle
        );
    }
}
