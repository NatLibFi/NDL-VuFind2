<?php

/**
 * Reservation list test base class
 *
 * PHP version 8
 *
 * Copyright (C) The National Library of Finland 2024.
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

namespace Finna\ReservationList;

use Finna\View\Helper\Root\ReservationList as ReservationListHelper;
use Generator;
use VuFind\Auth\ILSAuthenticator;
use VuFindTest\RecordDriver\TestHarness as RecordDriver;

use function is_callable;

/**
 * Reservation list test base class
 *
 * @category VuFind
 * @package  Tests
 * @author   Juha Luoma <juha.luoma@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:testing:unit_tests Wiki
 */
class ReservationListHelperTest extends \PHPUnit\Framework\TestCase
{
    /**
     * Runtime cache for drivers
     *
     * @var array
     */
    protected array $driverCache = [];

    /**
     * Function to get an instance of a reservation list helper
     *
     * @param array $yamlConfig    Yaml config
     * @param array $sectionConfig Section config
     *
     * @return ReservationListHelper
     */
    public function getReservationListHelper(array $yamlConfig = [], array $sectionConfig = []): ReservationListHelper
    {
        $constructorArgs = [
          $this->createMock(\Finna\ReservationList\ReservationListService::class),
          $this->createMock(\VuFind\Auth\ILSAuthenticator::class),
          $yamlConfig,
          $sectionConfig ?: [
            'enabled' => true,
          ],
        ];
        // Create an anonymous class for testing protected methods
        $helper = new class (...$constructorArgs) extends ReservationListHelper {
            /**
             * Expose protected function
             *
             * @param RecordDriver $driver Record driver
             *
             * @return array
             */
            public function callGetAvailableListsForRecord(RecordDriver $driver): array
            {
                return parent::getAvailableListsForRecord($driver);
            }

            /**
             * Set authenticator
             *
             * @param ILSAuthenticator $auth Authenticator ils
             *
             * @return self
             */
            public function setTestAuthenticator(ILSAuthenticator $auth)
            {
                $this->ilsAuthenticator = $auth;
                return $this;
            }

            /**
             * Get authenticator
             *
             * @return ILSAuthenticator
             */
            public function getTestAuthenticator()
            {
                return $this->ilsAuthenticator;
            }
        };
        $view = $this->createMock(\Laminas\View\Renderer\RendererInterface::class);
        $view->expects($this->any())->method('render')->willReturn('text from test');
        $helper->setView($view);
        return $helper;
    }

    /**
     * Test factory of helper
     *
     * @return void
     */
    public function testHelperFactory(): void
    {
        $created = $this->getReservationListHelper();
        $this->assertInstanceOf(ReservationListHelper::class, $created);
        $methods = [
        'getListProperties',
        'checkUserRightsForList',
        'renderReserveTemplate',
        'getReservationListsForUser',
        'getListsContainingRecord',
        'isFunctionalityEnabled',
        ];
        foreach ($methods as $method) {
            $this->assertTrue(method_exists($created, $method) && is_callable([$created, $method]));
        }
    }

    /**
     * List configurations provider
     *
     * @return Generator
     */
    public static function listConfigurationProvider(): Generator
    {
        $expected = [
        'test_institution' => [
            [
                'Identifier' => 'test_identifier_for_list_1',
                'Enabled' => true,
                'Recipient' => [
                    [
                        'name' => 'test_receiving_name_1',
                        'email' => 'test_receiving_email_1@test.org',
                    ],
                ],
                'Datasources' => [
                    'test_datasource_1',
                ],
                'LibraryCardSources' => [],
                'Information' => [],
                'Connection' => [
                    'type' => 'Database',
                ],
            ],
        ],
        ];

        $baseConfig = [
            'Institutions' => [
                'test_institution' => [
                    'Lists' => [
                        [
                            'Identifier' => 'test_identifier_for_list_1',
                            'Enabled' => true,
                            'Recipient' => [
                                [
                                    'name' => 'test_receiving_name_1',
                                    'email' => 'test_receiving_email_1@test.org',
                                ],
                            ],
                            'Datasources' => [
                                'test_datasource_1',
                            ],
                            'LibraryCardSources' => [],
                        ],
                    ],
                ],
            ],
        ];
        yield 'one_institution_and_1_list' => [$baseConfig, $expected];

        $secondList = [
            'Identifier' => 'test_identifier_for_list_2',
            'Enabled' => true,
            'Recipient' => [
                [
                    'name' => 'test_receiving_name_2',
                    'email' => 'test_receiving_email_2@test.org',
                ],
            ],
            'Datasources' => [
                'test_datasource_1',
            ],
            'LibraryCardSources' => [
                'test_library_source_2',
            ],
        ];
        $baseConfig['Institutions']['test_institution']['Lists'][] = $secondList;
        yield 'one_institution_and_2_lists' => [$baseConfig, $expected];

        $secondInstitutionWithList = [
            'Lists' => [
                [
                    'Identifier' => 'Something_odd_123',
                    'Enabled' => true,
                    'Recipient' => [
                        [
                            'name' => 'test_receiving_name_3',
                            'email' => 'test_receiving_email_3@test.org',
                        ],
                    ],
                    'Datasources' => [
                        'test_datasource_55',
                    ],
                    'LibraryCardSources' => [
                        'something',
                    ],
                ],
            ],
        ];
        $baseConfig['Institutions']['test_institution_2'] = $secondInstitutionWithList;
        yield 'two_institutions_and_3_lists' => [$baseConfig, $expected];

        $thirdList = [
            'Identifier' => 'test_identifier_for_list_3',
            'Enabled' => false,
            'Recipient' => [
            [
                'name' => 'test_receiving_name_3',
                'email' => 'test_receiving_email_3@test.org',
            ],
            ],
            'Datasources' => [
                'test_datasource_1',
            ],
            'LibraryCardSources' => [],
        ];
        $baseConfig['Institutions']['test_institution']['Lists'][] = $thirdList;
        yield 'two_institutions_and_4_lists' => [$baseConfig, $expected];
    }

    /**
     * Broken list configurations provider
     *
     * @return Generator
     */
    public static function brokenListConfigurationProvider(): Generator
    {
        $baseConfig = [
        'Institutions' => [
        'test_institution' => [
          'Lists' => [
                [
                    'Enabled' => true,
                    'Recipient' => [
                        [
                            'name' => 'test_receiving_name_1',
                            'email' => 'test_receiving_email_1@test.org',
                        ],
                    ],
                    'Datasources' => [
                        'test_datasource_1',
                    ],
                    'LibraryCardSources' => [],
                ],
          ],
        ],
        ],
        ];
        yield 'one_institution_and_list_with_no_identifier' => [$baseConfig, []];

        $baseConfig['Institutions']['test_institution']['Lists'][] = [
            'as-a.d-.as.asd' => 'sdlöasöldaölasöld',
            23123123 => '142welsalkdka',
            [
                'asdasd',
                'asdasd',
            ],
        ];
        yield 'one_institution_and_list_with_no_real_values' => [$baseConfig, []];
    }

    /**
     * Test that a record is found in 1 list and not in others.
     *
     * @param array $yamlConfig Provided test config
     * @param array $expected   Expected results
     *
     * @dataProvider listConfigurationProvider
     * @dataProvider brokenListConfigurationProvider
     *
     * @return void
     */
    public function testListCheckForRecord(array $yamlConfig, array $expected): void
    {
        $helper = $this->getReservationListHelper($yamlConfig);
        $rawData = [
            'Datasource' => 'test_datasource_1',
        ];
        $user = $this->createMock(\VuFind\Db\Entity\UserEntityInterface::class);
        $record = $this->getMockRecordDriver('rltest.123123', $rawData);
        $result = ($helper)($user)->callGetAvailableListsForRecord($record);

        $this->assertEquals($expected, $result);
    }

    /**
     * Test getListProperties function
     *
     * @param array $yamlConfig Provided test config
     *
     * @dataProvider listConfigurationProvider
     * @dataProvider brokenListConfigurationProvider
     *
     * @return void
     */
    public function testListProperties(array $yamlConfig): void
    {
        $helper = $this->getReservationListHelper($yamlConfig);
        $user = $this->createMock(\VuFind\Db\Entity\UserEntityInterface::class);
        $result = ($helper)($user)->getListProperties('test_institution', 'test_identifier_for_list_1');
        $defaultListProperties = [
            'Enabled' => false,
            'Recipient' => [],
            'Datasources' => [],
            'Information' => [],
            'LibraryCardSources' => [],
            'Connection' =>  [
                'type' => 'Database',
            ],
            'Identifier' => false,
        ];
        $this->assertArrayHasKey('properties', $result);
        $this->assertArrayHasKey('institution_information', $result);
        $this->assertArrayHasKey('translation_keys', $result);
        foreach ($defaultListProperties as $key => $value) {
            $this->assertArrayHasKey($key, $result['properties']);
        }
        $defaultTranslationKeys = [
            'title' => '',
            'description' => '',
        ];
        foreach ($defaultTranslationKeys as $key => $value) {
            $this->assertArrayHasKey($key, $result['translation_keys']);
        }
    }

    /**
     * Get a fake record driver
     *
     * @param string $id      ID to use
     * @param array  $rawData Raw data to set for the mock record driver
     *
     * @return RecordDriver
     */
    public function getMockRecordDriver(string $id, array $rawData): RecordDriver
    {
        if (!isset($this->driverCache[$id])) {
            $rawData['UniqueID'] ??= $id;
            $this->driverCache[$id] = new RecordDriver();
            $this->driverCache[$id]->setRawData($rawData);
        }
        return $this->driverCache[$id];
    }
}
