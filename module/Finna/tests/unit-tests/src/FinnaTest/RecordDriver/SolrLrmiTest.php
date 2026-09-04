<?php

/**
 * SolrLrmi Test Class.
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
 * along with this program; if not, see
 * <https://www.gnu.org/licenses/>.
 *
 * @category VuFind
 * @package  Tests
 * @author   Minna Rönkä <minna.ronka@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:testing:unit_tests Wiki
 */

namespace FinnaTest\RecordDriver;

use Finna\RecordDriver\SolrLrmi;

use function is_callable;

/**
 * SolrLrmi Test Class.
 *
 * @category VuFind
 * @package  Tests
 * @author   Minna Rönkä <minna.ronka@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:testing:unit_tests Wiki
 */
class SolrLrmiTest extends \PHPUnit\Framework\TestCase
{
    use \VuFindTest\Feature\FixtureTrait;

    /**
     * Function to get expected function data.
     *
     * @return array
     */
    public static function getTestFunctionsData(): array
    {
        return [
            [
                'getIdentifier',
                [
                    'urn:nbn:fi:oerfi-202402_00027263_1',
                ],
            ],
            [
                'getSummary',
                [
                    'Suomenkielinen kuvausteksti',
                    'Toinen suomenkielinen kuvausteksti',
                ],
            ],
            [
                'getNonPresenterAuthors',
                [
                    [
                        'name' => 'Suojellaan Lapsia ry, Protect Children',
                    ],
                ],
            ],
            [
                'getTopics',
                [
                    'digitaalinen media',
                    'digitalisaatio',
                ],
            ],
            [
                'getMaterials',
                [
                    [
                        'url' => 'https://materiaalilinkki1.pdf',
                        'pdfUrl' => null,
                        'title' => 'MyFriendToo-juliste 1',
                        'format' => 'pdf',
                        'filesize' => '146204',
                        'position' => 2,
                    ],
                    [
                        'url' => 'https://materiaalilinkki2.pdf',
                        'pdfUrl' => null,
                        'title' => 'MyFriendToo-juliste 2',
                        'format' => 'pdf',
                        'filesize' => '159732',
                        'position' => 3,
                    ],
                    [
                        'url' => 'https://materiaalilinkkienglanti.pdf',
                        'pdfUrl' => null,
                        'title' => 'MyFriendToo-poster 1',
                        'format' => 'pdf',
                        'filesize' => '157766',
                        'position' => 5,
                    ],
                    [
                        'url' => 'https://materiaalilinkkiruotsi.pdf',
                        'pdfUrl' => null,
                        'title' => 'MyFriendToo-affisch 1',
                        'format' => 'pdf',
                        'filesize' => '156683',
                        'position' => 7,
                    ],
                ],
            ],
            [
                'getEducationalUse',
                [
                    'Ohjeistus',
                ],
            ],
        ];
    }

    /**
     * Test functions.
     *
     * @param string $function Function of the driver to test
     * @param mixed  $expected Result to be expected
     *
     * @return void
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('getTestFunctionsData')]
    public function testFunctions(
        string $function,
        $expected
    ): void {
        $driver = $this->getDriver('lrmi_test.xml', language: 'fi');
        $this->assertTrue(is_callable([$driver, $function], true));
        $this->assertEquals(
            $expected,
            $driver->$function()
        );
    }

    /**
     * Function to get summary data.
     *
     * @return array
     */
    public static function getSummaryData(): array
    {
        return [
            [
                'fi',
                [
                'Suomenkielinen kuvausteksti',
                'Toinen suomenkielinen kuvausteksti',
                ],
            ],
            [
                'en',
                [
                'Description in English',
                ],
            ],
            [
                'sv',
                [
                'Deskription på svenska',
                ],
            ],
            [
                'se',
                [
                'Suomenkielinen kuvausteksti',
                'Toinen suomenkielinen kuvausteksti',
                ],
            ],
        ];
    }

    /**
     * Test getSummary.
     *
     * @param string $language Language
     * @param array  $expected Result to be expected
     *
     * @return void
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('getSummaryData')]
    public function testSummary(
        string $language,
        array $expected
    ): void {
        $driver = $this->getDriver('lrmi_test.xml', language: $language);
        $this->assertEquals(
            $expected,
            $driver->getSummary()
        );
    }

    /**
     * Function to get expected titles data.
     *
     * @return array
     */
    public static function getTitlesData(): array
    {
        return [
            [
                [
                    'title' => 'Pääotsikko',
                    'title_fi' => 'Pääotsikko',
                    'title_en' => 'Title in English',
                    'title_sv' => 'Titel på svenska',
                    'title_se' => '',
                    'title_alt' => ['Pääotsikko', 'Vaihtoehtoinen otsikko 1', 'Title in English'],
                ],
                [
                    'titles' => [
                        'en' => 'Title in English',
                        'fi' => 'Pääotsikko',
                        'sv' => 'Titel på svenska',
                        'se' => 'Pääotsikko',
                    ],
                    'altTitles' => [
                        'en' => ['Pääotsikko', 'Vaihtoehtoinen otsikko 1'],
                        'fi' => ['Title in English', 'Vaihtoehtoinen otsikko 1'],
                        'sv' => ['Pääotsikko', 'Title in English', 'Vaihtoehtoinen otsikko 1'],
                        'se' => ['Title in English', 'Vaihtoehtoinen otsikko 1'],
                    ],
                ],
            ],
        ];
    }

    /**
     * Test getAlternativeTitles.
     *
     * @param array $titles   Title index values to test
     * @param array $expected Result to be expected
     *
     * @return void
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('getTitlesData')]
    public function testTitles(
        array $titles,
        array $expected,
    ): void {
        $rawData = [
            'title' => $titles['title'],
            'title_fi_txt' => $titles['title_fi'],
            'title_en_txt' => $titles['title_en'],
            'title_sv_txt' => $titles['title_sv'],
            'title_se_txt' => $titles['title_se'],
            'title_alt' => $titles['title_alt'],
        ];
        $driver = $this->getDriver('lrmi_test.xml', overrides: $rawData);
        $this->assertEquals(
            $expected['altTitles']['en'],
            $driver->getAlternativeTitles()
        );
        $this->assertEquals(
            $expected['titles']['en'],
            $driver->getTitle()
        );
        $driver = $this->getDriver('lrmi_test.xml', overrides: $rawData, language: 'fi');
        $this->assertEquals(
            $expected['altTitles']['fi'],
            $driver->getAlternativeTitles()
        );
        $this->assertEquals(
            $expected['titles']['fi'],
            $driver->getTitle()
        );
        $driver = $this->getDriver('lrmi_test.xml', overrides: $rawData, language: 'sv');
        $this->assertEquals(
            $expected['altTitles']['sv'],
            $driver->getAlternativeTitles()
        );
        $this->assertEquals(
            $expected['titles']['sv'],
            $driver->getTitle()
        );
        $driver = $this->getDriver('lrmi_test.xml', overrides: $rawData, language: 'se');
        $this->assertEquals(
            $expected['altTitles']['se'],
            $driver->getAlternativeTitles()
        );
        $this->assertEquals(
            $expected['titles']['se'],
            $driver->getTitle()
        );
    }

    /**
     * Get a record driver with fake data.
     *
     * @param string $recordXml    Xml record to use for the test
     * @param array  $overrides    Fixture fields to override.
     * @param array  $searchConfig Search configuration.
     * @param string $language     Language
     *
     * @return SolrLrmi
     */
    protected function getDriver(
        string $recordXml,
        $overrides = [],
        $searchConfig = [],
        $language = 'en',
    ): SolrLrmi {
        $fixture = $this->getFixture("lrmi/$recordXml", 'Finna');
        $record = new SolrLrmi(
            null,
            null,
            new \VuFind\Config\Config($searchConfig)
        );
        $localeConfig = [
            'Site' => [
                'language' => 'fi',
                'fallback_languages' => 'fi,en',
                'browserDetectLanguage' => false,
            ],
            'Languages' => [
                'fi' => 'Finnish',
                'en' => 'English',
                'sv' => 'Swedish',
                'en-gb' => 'British English',
                'se' => 'Northern Sámi',
            ],
        ];
        $localeConfig = new \VuFind\Config\Config($localeConfig);
        $record->attachLocaleSettings(new \VuFind\I18n\Locale\LocaleSettings($localeConfig));
        $record->setPreferredLanguage($language);
        $defaultData = [
            'fullrecord' => $fixture,
        ];
        $record->setRawData(array_merge($defaultData, $overrides));
        return $record;
    }
}
