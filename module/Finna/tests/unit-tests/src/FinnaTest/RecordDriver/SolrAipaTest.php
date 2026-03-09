<?php

/**
 * AIPA test class.
 *
 * PHP version 8
 *
 * Copyright (C) The National Library of Finland 2023-2026.
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
 * @package  Tests
 * @author   Aleksi Peebles <aleksi.peebles@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:testing:unit_tests Wiki
 */

namespace Finna\RecordDriver;

use Finna\Record\Loader;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use VuFind\Config\Config;
use VuFindTest\Feature\FixtureTrait;

/**
 * AIPA test class.
 *
 * @category VuFind
 * @package  Tests
 * @author   Aleksi Peebles <aleksi.peebles@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:testing:unit_tests Wiki
 */
class SolrAipaTest extends TestCase
{
    use FixtureTrait;

    protected $pluginManager;

    protected $recordLoader;

    /**
     * Test filtered XML for public APIs.
     *
     * @return void
     */
    public function testFilteredXML(): void
    {
        $driver = $this->getSolrAipaDriver();
        $this->assertXmlStringEqualsXmlString(
            $this->getFixture('aipa/aipa_test_filtered.xml', 'Finna'),
            $driver->getFilteredXML()
        );
    }

    /**
     * Data provider for testDriverMethods.
     *
     * @return \Iterator
     */
    public static function driverMethodsProvider(): \Iterator
    {
        yield [
            'getSummary',
            ['First line of a long description.'],
        ];

        yield [
            'getAllImages',
            [
                [
                    'urls' => [
                        'small' => 'http://localhost/image',
                        'medium' => 'http://localhost/image',
                        'large' => 'http://localhost/image',
                    ],
                    'description' => '',
                    'rights' => [],
                    'downloadable' => false,
                ],
            ],
        ];

        yield [
            'getAllSubjectHeadings',
            [
                ['Subject'],
            ],
        ];

        yield [
            'getAllSubjectHeadingsExtended',
            [
                [
                    'heading' => [
                        'Subject',
                    ],
                    'type' => 'subject',
                    'detail' => '',
                    'authType' => '',
                    'source' => 'local',
                    'id' => 'foo',
                    'ids' => [
                        'foo',
                    ],
                ],
            ],
        ];

        yield [
            'getSubjectDates',
            [
                [
                    'heading' => [
                        '2026',
                    ],
                    'type' => 'subject',
                    'detail' => '',
                    'authType' => '',
                ],
            ],
        ];

        yield [
            'getSubjectPlaces',
            [
                'Helsinki',
            ],
        ];

        yield [
            'getSubjectPlacesExtended',
            [
                [
                    'heading' => [
                        'Helsinki',
                    ],
                    'type' => 'place',
                    'detail' => '',
                    'authType' => '',
                ],
            ],
        ];

        yield [
            'getRelatedEvents',
            [
                'So related',
            ],
        ];

        yield [
            'getRelatedEventsExtended',
            [
                [
                    'heading' => [
                        'So related',
                    ],
                    'type' => 'subject',
                    'detail' => '',
                    'authType' => '',
                ],
            ],
        ];

        yield [
            'getGeneralNotes',
            [],
        ];

        yield [
            'getType',
            'aipa-education',
        ];

        yield [
            'getTopics',
            [
                ['Subject'],
            ],
        ];

        yield [
            'getProvenance',
            'Provenance',
        ];

        yield [
            'getAdditionalInformation',
            'Additional information',
        ];
    }

    /**
     * Test driver methods.
     *
     * @param string $method   Method
     * @param mixed  $expected Expected result
     *
     * @return void
     */
    #[DataProvider('driverMethodsProvider')]
    public function testDriverMethods(string $method, $expected): void
    {
        $driver = $this->getSolrAipaDriver();
        $this->assertSame(
            $expected,
            $driver->$method()
        );
        // Check again to ensure any caching works:
        $this->assertSame(
            $expected,
            $driver->$method()
        );
    }

    /**
     * Test getAccessRestrictionsType.
     *
     * @return void
     */
    public function testGetAccessRestrictionsType(): void
    {
        $driver = $this->getSolrAipaDriver();
        $this->assertSame(
            [
                'copyright' => 'CC BY 4.0',
                'link' => 'http://creativecommons.org/licenses/by/4.0/deed.en',
            ],
            $driver->getAccessRestrictionsType('en-gb')
        );
        $this->assertSame(
            [
                'copyright' => 'CC BY 4.0',
                'link' => 'http://creativecommons.org/licenses/by/4.0/deed.fi',
            ],
            $driver->getAccessRestrictionsType('fi')
        );
        $this->assertSame(
            [
                'copyright' => 'CC BY 4.0',
            ],
            $driver->getAccessRestrictionsType('foo')
        );
    }

    /**
     * Test getEncapsulatedContentTypeRecords.
     *
     * @return void
     */
    public function testGetEncapsulatedContentTypeRecords(): void
    {
        $driver = $this->getSolrAipaDriver();
        $records = $driver->getEncapsulatedContentTypeRecords();
        $this->assertIsArray($records);
        $this->assertCount(1, $records);
        $expectedId = 'aipa.node-2785|oai:aineistopaketit.finna.fi:2787';
        $this->assertSame(
            $expectedId,
            key($records)
        );
        $this->assertSame(
            $expectedId,
            $records[$expectedId]->getUniqueID()
        );
    }

    /**
     * Get an AIPA record driver with fake data.
     *
     * @return SolrAipa
     */
    protected function getSolrAipaDriver(): SolrAipa
    {
        $fixture = $this->getFixture('aipa/aipa_test.xml', 'Finna');
        $record = new SolrAipa(
            new Config(
                [
                    'ImageRights' => [
                        'fi' => [
                            'CC BY 4.0' => 'http://creativecommons.org/licenses/by/4.0/deed.fi',
                        ],
                        'en-gb' => [
                            'CC BY 4.0' => 'http://creativecommons.org/licenses/by/4.0/deed.en',
                        ],
                    ],
                    'RightsMap' => [
                        'CCBY40' => 'CC BY 4.0',
                    ],
                ]
            )
        );
        $record->attachRecordDriverManager($this->getPluginManager());
        $record->setRawData([
            'id' => 'aipa.node-2785',
            'fullrecord' => $fixture,
            'description' => "First line of a long description.\n\nSecond line of a long description.",
        ]);
        return $record;
    }

    /**
     * Get an AIPA LRMI record driver.
     *
     * @return AipaLrmi
     */
    protected function getAipaLrmiDriver(): AipaLrmi
    {
        $record = new AipaLrmi();
        $record->attachRecordDriverManager($this->getPluginManager());
        $record->attachRecordLoader($this->getRecordLoader());
        return $record;
    }

    /**
     * Get a mock record driver plugin manager.
     *
     * @return PluginManager
     */
    protected function getPluginManager(): PluginManager
    {
        if (!isset($this->pluginManager)) {
            $pluginManager = $this->createMock(PluginManager::class);
            $pluginManager
                ->method('get')
                ->willReturnCallback(function ($name) {
                    switch ($name) {
                        case 'AipaLrmi':
                            return $this->getAipaLrmiDriver();
                        case 'CuratedRecord':
                            return new CuratedRecord();
                    }
                });
            $this->pluginManager = $pluginManager;
        }
        return $this->pluginManager;
    }

    /**
     * Get a mock record loader.
     *
     * @return Loader
     */
    protected function getRecordLoader(): Loader
    {
        if (!isset($this->recordLoader)) {
            $recordLoader = $this->createMock(Loader::class);
            $recordLoader
                ->method('load')
                ->willReturnCallback(function ($id) {
                    $record = new SolrDefault();
                    $record->setRawData(['id' => $id]);
                    return $record;
                });
            $this->recordLoader = $recordLoader;
        }
        return $this->recordLoader;
    }
}
