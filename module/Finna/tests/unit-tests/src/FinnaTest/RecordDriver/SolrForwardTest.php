<?php
/**
 * SolrForward Test Class
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
 * @package  Tests
 * @author   Juha Luoma <juha.luoma@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:testing:unit_tests Wiki
 */
namespace FinnaTest\RecordDriver;

use Finna\RecordDriver\SolrForward;

/**
 * SolrDefault Record Driver Test Class
 *
 * @category VuFind
 * @package  Tests
 * @author   Juha Luoma <juha.luoma@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:testing:unit_tests Wiki
 */
class SolrForwardTest extends \PHPUnit\Framework\TestCase
{
    use \VuFindTest\Feature\FixtureTrait;

    /**
     * Test primary authors.
     *
     * @return void
     */
    public function testGetPrimaryAuthors()
    {
        $driver = $this->getDriver();
        $this->assertEquals(
            [
                [
                    "tag" => "elotekija",
                    "name" => "Juha Kuoma",
                    "role" => "drt",
                    "id" => "elonet_henkilo_1",
                    "type" => "elonet_henkilo",
                    "roleName" => "",
                    "description" => "",
                    "uncredited" => "",
                    "idx" => 1,
                    "tehtava" => "ohjaus",
                    "finna-activity-code" => "D02",
                    "relator" => "D02"
                ],
            ],
            $driver->getNonPresenterPrimaryAuthors()
        );
    }

    /**
     * Test producers.
     * 
     * @return void
     */
    public function testGetProducers()
    {
        $driver = $this->getDriver();
        $this->assertEquals( 
            [
                [
                    'tag' => 'elotuotantoyhtio',
                    'name' => 'Finna-filmi Oy',
                    'role' => 'pro',
                    'id' => 'elonet_yhtio_218057',
                    'type' => 'elonet_yhtio',
                    'roleName' => '',
                    'description' => '',
                    'uncredited' => '',
                    'idx' => 100000,
                    'finna-activity-code' => 'E10',
                    'relator' => 'E10',
                ]
            ],
            $driver->getProducers()
        );
    }

    /**
     * Function to get testGetPresenters data.
     *
     * @return array
     */
    public function getPresentersData(): array
    {
        return [
            'presentersTest' => [
                'performer',
                [
                    'presenters' => [ 
                        [
                            'tag' => 'eloesiintyja',
                            'name' => 'Esiin Tyjä',
                            'role' => '',
                            'id' => 'elonet_henkilo_1312480',
                            'type' => 'elonet_henkilo',
                            'roleName' => '',
                            'description' => '',
                            'uncredited' => '',
                            'idx' => 80000,
                            'finna-activity-code' => 'E99',
                            'finna-activity-text' => 'dokumentti-esiintyjä',
                            'relator' => 'E99',
                        ],
                        [
                            'tag' => 'eloesiintyja',
                            'name' => 'Ääre M. Es',
                            'role' => '',
                            'id' => 'elonet_henkilo_1320375',
                            'type' => 'elonet_henkilo',
                            'roleName' => 'Tämä on määre',
                            'description' => '',
                            'uncredited' => '',
                            'idx' => 90000,
                            'finna-activity-code' => 'E99',
                            'finna-activity-text' => 'dokumentti-esiintyjä',
                            'relator' => 'E99',
                            'elokuva-eloesiintyja-maare' => 'Tämä on määre',
                        ],
                    ],
                ],
            ],
            'uncreditedPerformersTest' => [
                'uncreditedPerformer',
                [
                    'presenters' => 
                    [
                        [
                            'tag' => 'elokreditoimatonesiintyja',
                            'name' => 'Doku M. Entti',
                            'role' => '',
                            'id' => 'elonet_henkilo_1344654',
                            'type' => 'elonet_henkilo',
                            'roleName' => 'Kreditöimätön esiintyjä',
                            'description' => '',
                            'uncredited' => true,
                            'idx' => 150000,
                            'finna-activity-code' => 'E99',
                            'finna-activity-text'
                                => 'kreditoimaton-dokumentti-esiintyjä',
                            'relator' => 'E99',
                            'elokuva-elokreditoimatonesiintyja-nimi'
                                => 'Doku M. Entti',
                            'elokuva-elokreditoimatonesiintyja-maare'
                                => 'Kreditöimätön esiintyjä',
                        ],
                        [
                            'tag' => 'elokreditoimatonesiintyja',
                            'name' => 'Doku M. Entti II',
                            'role' => '',
                            'id' => 'elonet_henkilo_1486496',
                            'type' => 'elonet_henkilo',
                            'roleName' => 'Kreditöimätön esiintyjä nr. 2',
                            'description' => '',
                            'uncredited' => true,
                            'idx' => 160000,
                            'finna-activity-code' => 'E99',
                            'finna-activity-text'
                                => 'kreditoimaton-dokumentti-esiintyjä',
                            'relator' => 'E99',
                            'elokuva-elokreditoimatonesiintyja-nimi'
                                => 'Doku M. Entti II',
                            'elokuva-elokreditoimatonesiintyja-maare'
                                => 'Kreditöimätön esiintyjä nr. 2',
                        ],
                    ], 
                ],
            ]
        ];
    }

    /**
     * Function to get testGetNonPresenterSecondaryAuthors data.
     *
     * @return array
     */
    public function getNonPresenterSecondaryAuthorsData(): array
    {
        return [
            'creditedTests' => 
            [  
                'credited',
                [
                    [
                        'tag' => 'elotekija',
                        'name' => 'Juha Kuoma',
                        'role' => 'drt',
                        'id' => 'elonet_henkilo_1',
                        'type' => 'elonet_henkilo',
                        'roleName' => '',
                        'description' => '',
                        'uncredited' => '',
                        'idx' => 1,
                        'tehtava' => 'ohjaus',
                        'finna-activity-code' => 'D02',
                        'relator' => 'D02',
                    ],
                    [
                        'tag' => 'elotekija',
                        'name' => 'Kuha Luoma',
                        'role' => 'aus',
                        'id' => 'elonet_henkilo_2',
                        'type' => 'elonet_henkilo',
                        'roleName' => '',
                        'description' => '',
                        'uncredited' => '',
                        'idx' => 20000,
                        'tehtava' => 'käsikirjoitus',
                        'finna-activity-code' => 'aus',
                        'relator' => 'aus',
                    ],
                    [
                        'tag' => 'elotekija',
                        'name' => 'Tuo T. Taja',
                        'role' => 'fmp',
                        'id' => 'elonet_henkilo_3',
                        'type' => 'elonet_henkilo',
                        'roleName' => '',
                        'description' => '',
                        'uncredited' => '',
                        'idx' => 30000,
                        'tehtava' => 'tuottaja',
                        'finna-activity-code' => 'fmp',
                        'relator' => 'fmp',
                    ],
                    [
                        'tag' => 'elotekija',
                        'name' => 'Konsul Tti',
                        'role' => 'tuotantokonsultti',
                        'id' => 'elonet_henkilo_4',
                        'type' => 'elonet_henkilo',
                        'roleName' => '',
                        'description' => '',
                        'uncredited' => '',
                        'idx' => 40000,
                        'tehtava' => 'tuotantokonsultti',
                        'finna-activity-code' => 'A99',
                        'finna-activity-text' => 'tuotantokonsultti',
                        'elokuva-elotekija-tehtava' => 'tuotantokonsultti',
                        'relator' => 'A99',
                    ],
                    [
                        'tag' => 'elotekija',
                        'name' => 'Assis Tentti',
                        'role' => 'tuotantoassistentti',
                        'id' => 'elonet_henkilo_5',
                        'type' => 'elonet_henkilo',
                        'roleName' => '',
                        'description' => '',
                        'uncredited' => '',
                        'idx' => 50000,
                        'tehtava' => 'tuotantoassistentti',
                        'finna-activity-code' => 'A99',
                        'finna-activity-text' => 'tuotantoassistentti',
                        'elokuva-elotekija-tehtava' => 'tuotantoassistentti',
                        'relator' => 'A99',
                    ],
                    [
                        'tag' => 'elotekijayhtio',
                        'name' => 'Tekevä Yhtiö Oy',
                        'role' => 'Yhtiön tehtävä',
                        'id' => 'elonet_yhtio_956916',
                        'type' => 'elonet_yhtio',
                        'roleName' => '',
                        'description' => '',
                        'uncredited' => '',
                        'idx' => 70000,
                        'tehtava' => 'Yhtiön tehtävä',
                        'finna-activity-code' => 'A99',
                        'finna-activity-text' => 'Yhtiön tehtävä',
                        'elokuva-elotekijayhtio-tehtava' => 'Yhtiön tehtävä',
                        'relator' => 'A99',
                    ],
                    [
                        'tag' => 'elolevittaja',
                        'name' => 'Levittäjä Oy',
                        'role' => 'fds',
                        'id' => 'elonet_yhtio_210941',
                        'type' => 'elonet_yhtio',
                        'roleName' => '',
                        'description' => '',
                        'uncredited' => '',
                        'idx' => 130000,
                        'finna-activity-code' => 'fds',
                        'relator' => 'fds',
                        'elokuva-elolevittaja-vuosi' => '2001',
                        'elokuva-elolevittaja-levitystapa' => 'teatterilevitys',
                    ],
                ],
            ],
            'ensemblesTests' =>
            [   
                'ensembles',
                [
                    [
                        'tag' => 'elotekijakokoonpano',
                        'name' => 'Joku kuoro',
                        'role' => 'kuoro',
                        'id' => 'elonet_kokoonpano_1480640',
                        'type' => 'elonet_kokoonpano',
                        'roleName' => '',
                        'description' => '',
                        'uncredited' => '',
                        'idx' => 60000,
                        'tehtava' => 'kuoro',
                        'finna-activity-code' => 'A99',
                        'finna-activity-text' => 'kuoro',
                        'elokuva-elotekijakokoonpano-tehtava' => 'kuoro',
                        'relator' => 'A99',
                    ],
                ],
            ],
            'uncreditedTests' =>
            [   
                'uncredited',
                [
                    [
                        'tag' => 'elokreditoimatontekija',
                        'name' => 'Valo K. Uvaus',
                        'role' => 'valokuvat',
                        'id' => 'elonet_henkilo_107674',
                        'type' => 'elonet_henkilo',
                        'roleName' => '',
                        'description' => '',
                        'uncredited' => true,
                        'idx' => 140000,
                        'tehtava' => 'valokuvat',
                        'finna-activity-code' => 'A99',
                        'finna-activity-text' => 'valokuvat',
                        'elokuva-elokreditoimatontekija-tehtava' => 'valokuvat',
                        'relator' => 'A99',
                        'elokuva-elokreditoimatontekija-nimi' => 'Valo K. Uvaus',
                    ],
                ],
            ]
        ];
    }

    /**
     * Test presenters.
     * @dataProvider getPresentersData
     * 
     * @param string $key      Key of the array to test.
     * @param array  $expected Result to be expected.
     * 
     * @return void
     */
    public function testGetPresenters(string $key, array $expected): void
    {
        $driver = $this->getDriver();
        $this->assertEquals( 
            $expected,
            $driver->getPresenters()[$key]
        );
    }

    /**
     * Test funders.
     * 
     * @return void
     */
    public function testGetFunders(): void
    {
        $driver = $this->getDriver();
        $this->assertEquals(
            [ 
                [
                  'tag' => 'elorahoitusyhtio',
                  'name' => 'Rahoitus tuotantotuki',
                  'role' => 'fnd',
                  'id' => 'elonet_yhtio_11',
                  'type' => 'elonet_yhtio',
                  'roleName' => '',
                  'description' => '',
                  'uncredited' => '',
                  'idx' => 110000,
                  'finna-activity-code' => 'fnd',
                  'relator' => 'fnd',
                  'elokuva-elorahoitusyhtio-rahoitustapa' => 'tuotantotuki',
                  'elokuva-elorahoitusyhtio-summa' => '159 779 €',
                  'amount' => '159 779 €',
                  'fundingType' => 'tuotantotuki',
                ],
                [
                  'tag' => 'elorahoitusyhtio',
                  'name' => 'Rahoitus yhteistyö',
                  'role' => 'fnd',
                  'id' => 'elonet_yhtio_710074',
                  'type' => 'elonet_yhtio',
                  'roleName' => '',
                  'description' => '',
                  'uncredited' => '',
                  'idx' => 120000,
                  'finna-activity-code' => 'fnd',
                  'relator' => 'fnd',
                  'elokuva-elorahoitusyhtio-henkilo' => 'Eila Werning',
                  'elokuva-elorahoitusyhtio-rahoitustapa' => 'yhteistyö',
                  'amount' => '',
                  'fundingType' => 'yhteistyö',
                ],
            ],
            $driver->getFunders()
        );
    }

    /**
     * Test funders.
     * 
     * @return void
     */
    public function testGetDistributors(): void
    {
        $driver = $this->getDriver();
        $this->assertEquals(
            [
                [
                  'tag' => 'elolevittaja',
                  'name' => 'Levittäjä Oy',
                  'role' => 'fds',
                  'id' => 'elonet_yhtio_210941',
                  'type' => 'elonet_yhtio',
                  'roleName' => '',
                  'description' => '',
                  'uncredited' => '',
                  'idx' => 130000,
                  'finna-activity-code' => 'fds',
                  'relator' => 'fds',
                  'elokuva-elolevittaja-vuosi' => '2001',
                  'elokuva-elolevittaja-levitystapa' => 'teatterilevitys',
                  'date' => '2001',
                  'method' => 'teatterilevitys',
                ],
            ],
            $driver->getDistributors()
        );
    }

    /**
     * Test nonpresenter secondaryauthors.
     * @dataProvider getNonPresenterSecondaryAuthorsData
     * 
     * @param string $key      Key of the array to test.
     * @param array  $expected Result to be expected.
     * 
     * @return void
     */
    public function testGetNonPresenterSecondaryAuthors(
        string $key,
        array $expected
    ): void {
        $driver = $this->getDriver();
        $this->assertEquals( 
            $expected,
            $driver->getNonPresenterSecondaryAuthors()[$key]
        );
    }

    /**
     * Function to get testEvents data with array expected.
     *
     * @return array
     */
    public function getEventsArrayData(): array
    {
        return [
            [
                'getAccessRestrictions',
                []
            ],
            [
                'getDescription',
                [
                    'Tämä on sisällön kuvaus.'
                ]
            ],
            [
                'getGeneralNotes',
                [
                    'Tässä on huomautukset.'
                ]
            ],
            [
                'getAllSubjectHeadings',
                [
                    ['Testi'],
                    ['Unit'],
                    ['Forward']
                ]
            ],
            [
                'getAlternativeTitles',
                [
                    'Zoo (swe)',
                    'Animals (working title)',
                    'Park (test name)'
                ]
            ],
            [
                'getAwards',
                [
                    'Paras elokuva.',
                    'Best movie.',
                    'Good movie.'
                ]
            ],
            [
                'getPlayingTimes',
                [
                    '1 min'
                ]
            ],
            [
                'getPremiereTheaters',
                [
                    'Leppävaara: Sellosali 1',
                    'Karjaa: Bio Pallas'
                ]
            ],
            [
                'getBroadcastingInfo',
                [
                    [
                        'time' => '7.05.1995',
                        'place' => 'Kanava 1',
                        'viewers' => '1 000 (mediaani)' 
                    ],
                    [
                        'time' => '15.05.2011',
                        'place' => 'Kanava 2',
                        'viewers' => '5 000'
                    ]    
                ]
            ],
            [
                'getFestivalInfo',
                [
                    [
                        'name' => 'Ensimmäinen festivaaliosallistuminen',
                        'region' => 'Leppävaara, Suomi',
                        'date' => '1990'
                    ],
                    [
                        'name' => 'Toinen festivaaliosallistuminen',
                        'region' => 'Lahti, Suomi',
                        'date' => '1991'
                    ]
                ]
            ],
            [
                'getForeignDistribution',
                [
                    [
                        'name' => 'Mat',
                        'region' => 'Ruotsi'
                    ],
                    [
                        'name' => 'Pat',
                        'region' => 'Norja'
                    ]
                ]
            ],
            [
                'getOtherScreenings',
                [
                    [
                        'name' => 'ennakkoesitys',
                        'region' => 'Mordor, Keskimaa',
                        'date' => '03.03.2000'
                    ]
                ]
            ],
            [
                'getInspectionDetails',
                [
                    [
                        'inspector' => 'T',
                        'number' => 'A-5',
                        'format' => '1 mm',
                        'length' => '1 m',
                        'runningtime' => '1 min',
                        'agerestriction' => 'S',
                        'additional' => 'Tarkastajat: Tarkastajat OY',
                        'office' => 'Finna-filmit Oy',
                        'date' => '15.02.2001'
                    ]
                ]
            ],
        ];
    }

    /**
     * Function to get testEvents data with string expected.
     *
     * @return array
     */
    public function getEventsStringData(): array
    {
        return [
            [
                'getColor',
                'väri'
            ],
            [
                'getColorSystem',
                'rgb'
            ],
            [
                'getType',
                'kauhu, draama'
            ],
            [
                'getAspectRatio',
                '1,75:1'
            ],
            [
                'getMusicInfo',
                'Tästä musiikki-infosta poistuu br merkki alusta.'
            ],
            [
                'getOriginalWork',
                'lotr'
            ],
            [
                'getPressReview',
                'Tässä on lehdistöarvio.'
            ],
            [
                'getSound',
                'ääni'
            ],
            [
                'getSoundSystem',
                '6+1'
            ],
            [
                'getProductionCost',
                '5 €'
            ],
            [
                'getPremiereTime',
                '01.01.2001'
            ],
            [
                'getNumberOfCopies',
                '1'
            ],
            [
                'getAmountOfViewers',
                '1 100'
            ],
            [
                'getAgeLimit',
                'S'
            ],
            [
                'getLocationNotes',
                'Tässä on tietoa kuvauspaikkahuomautuksista.'
            ],
            [
                'getFilmingDate',
                '10.6.1996 - syksy 2000 (Lähde: ctrl+c 22.2.2010).'
            ],
            [
                'getArchiveFilms',
                'Infoa arkistoaineistosta.'
            ]
        ];
    }

    /**
     * Test nonpresenter secondaryauthors.
     * @dataProvider getEventsStringData
     * 
     * @param string $function Function of the driver to test.
     * @param string $expected Result to be expected.
     * 
     * @return void
     */
    public function testEvents(
        string $function,
        string $expected
    ): void {
        $driver = $this->getDriver();
        $this->assertEquals( 
            $expected,
            $driver->$function()
        );
    }

    /**
     * Test nonpresenter secondaryauthors.
     * @dataProvider getEventsArrayData
     * 
     * @param string $function Function of the driver to test.
     * @param array  $expected Result to be expected.
     * 
     * @return void
     */
    public function testEventsWithArrayExpected(
        string $function,
        array $expected
    ): void {
        $driver = $this->getDriver();
        $this->assertEquals( 
            $expected,
            $driver->$function()
        );
    }

    /**
     * Get a record driver with fake data.
     *
     * @param array $overrides    Fixture fields to override.
     * @param array $searchConfig Search configuration.
     *
     * @return SolrForward
     */
    protected function getDriver($overrides = [], $searchConfig = []): SolrForward
    {
        $fixture = $this->getFixture('forward/forward_test.xml', 'Finna');
        $record = new SolrForward(
            null,
            null,
            new \Laminas\Config\Config($searchConfig)
        );
        $record->setRawData($fixture);
        $record->setLazyRecordXml($fixture);
        return $record;
    }
}
