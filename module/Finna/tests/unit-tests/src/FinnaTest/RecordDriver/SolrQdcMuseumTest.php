<?php
/**
 * SolrQdc Museum Test Class
 *
 * PHP version 8
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
 * @package  Tests
 * @author   Juha Luoma <juha.luoma@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:testing:unit_tests Wiki
 */
namespace FinnaTest\RecordDriver;

use Finna\RecordDriver\SolrQdc;

/**
 * SolrQdc Museum Test Class
 *
 * @category VuFind
 * @package  Tests
 * @author   Juha Luoma <juha.luoma@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:testing:unit_tests Wiki
 */
class SolrQdcMuseumTest extends \PHPUnit\Framework\TestCase
{
    use \VuFindTest\Feature\FixtureTrait;

    /**
     * Function to get expected function data
     *
     * @return array
     */
    public function getTestFunctionsData(): array
    {
        return [
            [
                'getAllImages',
                [
                    0 => [
                        'urls' => [
                            'large' => 'https://www.savanni.art.collection.org/large/ducksinharmony.jpg',
                            'small' => 'https://www.savanni.art.collection.org/square/ducksinharmony.jpg',
                            'medium' => 'https://www.savanni.art.collection.org/medium/ducksinharmony.jpg',
                            'original' => 'https://www.savanni.art.collection.org/original/ducksinharmony.jpg'
                        ],
                        'description' => '',
                        'rights' => [
                            'copyright' => '',
                            'link' => false
                        ],
                        'highResolution' => [
                            'original' => [
                                0 => [
                                    'data' => [],
                                    'url' => 'https://www.savanni.art.collection.org/original/ducksinharmony.jpg',
                                    'format' => 'jpg'
                                ]
                            ]
                        ],
                        'downloadable' => false
                    ]
                ],
            ],
            [
                'getAllRecordLinks',
                [
                    0 => [
                        'value' => 'Ducks in the universe',
                        'link' => [
                            'value' => 'Ducks in the universe',
                            'type' => 'allFields'
                        ]
                    ]
                ],
            ],
            [
                'getSeries',
                []
            ],
            [
                'getIdentifier',
                [
                    0 => 'TM 1234'
                ]
            ],
            [
                'getKeywords',
                []
            ],
            [
                'getISBNs',
                []
            ],
            [
                'getOtherIdentifiers',
                [
                    0 => [
                        'data' => 'Q123456789',
                        'detail' => 'wikidata',
                    ],
                    1 => [
                        'data' => 'TM 1234',
                        'detail' => 'wikidata:P217'
                    ]
                ]
            ],
            [
                'getURLs',
                []
            ],
            [
                'getEducationPrograms',
                []
            ],
            [
                'getPhysicalDescriptions',
                [
                    0 => '2.1 cm x 2.3 cm'
                ]
            ],
            [
                'getPhysicalMediums',
                [
                    0 => 'Akryyli',
                    1 => 'Kangas'
                ]
            ],
            [
                'getDescriptions',
                [
                    0 => 'painting by Juha Kuoma'
                ]
            ],
            [
                'getAbstracts',
                []
            ],
            [
                'getDescriptionURL',
                false
            ]
        ];
    }

    /**
     * Test functions with return value array
     *
     * @param string $function Function of the driver to test
     * @param mixed  $expected Result to be expected
     *
     * @dataProvider getTestFunctionsData
     *
     * @return void
     */
    public function testFunctions(
        string $function,
        $expected
    ): void {
        $driver = $this->getDriver();
        $this->assertTrue(is_callable([$driver, $function], true));
        $this->assertEquals(
            $expected,
            $driver->$function()
        );
    }

    /**
     * Get a record driver with fake data
     *
     * @param array $overrides    Fixture fields to override
     * @param array $searchConfig Search configuration
     *
     * @return SolrQdc
     */
    protected function getDriver($overrides = [], $searchConfig = []): SolrQdc
    {
        $fixture = $this->getFixture('qdc/qdc_museum_test.xml', 'Finna');
        $config = [
            'Record' => [
                'allowed_external_hosts_mode' => 'disable',
            ],
            'ImageRights' => [
                'fi' => [
                    'CC BY 4.0' => 'http://creativecommons.org/licenses/by/4.0/deed.fi'
                ],
                'en-gb' => [
                    'CC BY 4.0' => 'http://creativecommons.org/licenses/by/4.0/deed.en'
                ],
                'sv' => [
                    'CC BY 4.0' => 'http://creativecommons.org/licenses/by/4.0/deed.sv'
                ]
            ]
        ];
        $record = new SolrQdc(
            $config,
            $config,
            new \Laminas\Config\Config($searchConfig)
        );
        $record->setRawData(['id' => 'knp-247394', 'fullrecord' => $fixture]);
        return $record;
    }
}
