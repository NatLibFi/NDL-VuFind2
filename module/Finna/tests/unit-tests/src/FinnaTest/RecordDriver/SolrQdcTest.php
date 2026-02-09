<?php

/**
 * SolrQdc Institutional Repository Test Class
 *
 * PHP version 8
 *
 * Copyright (C) The National Library of Finland 2022-2026.
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
 * @author   Juha Luoma <juha.luoma@helsinki.fi>
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:testing:unit_tests Wiki
 */

namespace FinnaTest\RecordDriver;

use Finna\RecordDriver\SolrQdc;

use function is_callable;

/**
 * SolrQdc Institutional Repository Test Class
 *
 * @category VuFind
 * @package  Tests
 * @author   Juha Luoma <juha.luoma@helsinki.fi>
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:testing:unit_tests Wiki
 */
class SolrQdcTest extends \PHPUnit\Framework\TestCase
{
    use \VuFindTest\Feature\FixtureTrait;

    /**
     * Function to get expected function data for institutional repository record.
     *
     * @return \Iterator
     */
    public static function getTestInstitutionalRepositoryRecordData(): \Iterator
    {
        $allTests = [
            [
                'getAbstracts',
                [
                    'fi' => 'Tosi abstraktia',
                    'en-gb' => 'So abstract',
                ],
            ],
            [
                'getAccessRestrictions',
                [
                    'Restricted Access',
                ],
            ],
            [
                'getAllImages',
                [
                    [
                        'urls' => [
                            'large' => 'https://www.animals.of.earth.fi/duck.pdf',
                            'small' => 'https://www.animals.of.earth.fi/duck.pdf',
                            'medium' => 'https://www.animals.of.earth.fi/duck.pdf',
                        ],
                        'description' => '',
                        'rights' => [
                            'copyright' => 'CC BY 4.0',
                            'link' => 'http://creativecommons.org/licenses/by/4.0/deed.fi',
                            'description' => [
                                'This is a copyright description',
                                'This is a copyright which should also be displayed as description',
                            ],
                        ],
                        'pdf' => true,
                        'cacheSizes' => [
                            'medium' => 'small',
                        ],
                        'downloadable' => true,
                    ],
                ],
            ],
            [
                'getAllRecordLinks',
                [
                    [
                        'value' => 'Animals of Earth',
                        'link' => [
                            'value' => 'Animals of Earth',
                            'type' => 'allFields',
                        ],
                    ],
                ],
            ],
            [
                'getAlternativeTitles',
                [
                    'Alt Title',
                ],
            ],
            [
                'getDescriptionURL',
                false,
            ],
            [
                'getSeries',
                [
                    [
                        'name' => 'Animals of Earth - The Series',
                        'partNumber' => '11-2022',
                    ],
                ],
            ],
            [
                'getIdentifier',
                [],
            ],
            [
                'getKeywords',
                [
                    'keyword1',
                    'keyword2',
                ],
            ],
            [
                'getISBNs',
                [
                    '978-3-16-148410-0',
                    '978-3-16-148410-1',
                ],
            ],
            [
                'getOtherIdentifiers',
                [
                    [
                        'data' => '123-4-245-6',
                        'detail' => '',
                    ],
                ],
            ],
            [
                'getURLs',
                [
                    [
                        'url' => 'http://localhost/url1',
                    ],
                    [
                        'url' => 'http://localhost/url2',
                    ],
                ],
            ],
            [
                'getEducationPrograms',
                [
                    'Duck Education',
                ],
            ],
            [
                'getPhysicalDescriptions',
                [
                    'Format',
                    'Another Format',
                    'Extent',
                ],
            ],
            [
                'getPhysicalMediums',
                [
                    'No physical carrier',
                ],
            ],
            [
                'getDescriptions',
                [
                    'Description text',
                    'Additional description',
                ],
            ],
            [
                'getGeneralNotes',
                [
                    'Notification text',
                ],
            ],
        ];
        foreach ($allTests as $test) {
            $test[] = 'qdc/qdc_ir_test.xml';
            yield $test;
        }
        foreach ($allTests as $test) {
            $test[] = 'qdc/qdc_kk.xml';
            yield $test;
        }
    }

    /**
     * Test functions
     *
     * @param string $function Function of the driver to test
     * @param mixed  $expected Result to be expected
     * @param string $fixture  Fixture file
     *
     * @return void
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('getTestInstitutionalRepositoryRecordData')]
    public function testInstitutionalRepositoryRecordFunctions(
        string $function,
        $expected,
        string $fixture,
    ): void {
        $driver = $this->getInstitutionalRepositoryDriver(fixture: $fixture);
        $this->assertTrue(is_callable([$driver, $function], true));
        $this->assertSame(
            $expected,
            $driver->$function()
        );
        // Second time to test any caching:
        $this->assertSame(
            $expected,
            $driver->$function()
        );
    }

    /**
     * Function to get expected author data
     *
     * @return array
     */
    public static function getNonPresenterAuthorsData(): array
    {
        return [
            [
                'fi',
                [
                    [
                        'name' => 'Kiira Kirjoittaja',
                        'role' => 'aut',
                    ],
                    [
                        'name' => 'Piia Piirtäjä',
                        'role' => 'ill',
                    ],
                    [
                        'name' => 'Helsingin yliopisto',
                        'role' => '',
                    ],
                    [
                        'name' => 'School',
                        'role' => '',
                    ],
                ],
            ],
            [
                'sv',
                [
                    [
                        'name' => 'Kiira Kirjoittaja',
                        'role' => 'aut',
                    ],
                    [
                        'name' => 'Piia Piirtäjä',
                        'role' => 'ill',
                    ],
                    2 => [
                        'name' => 'Helsingfors universitet',
                        'role' => '',
                    ],
                    [
                        'name' => 'School',
                        'role' => '',
                    ],
                ],
            ],
        ];
    }

    /**
     * Test getNonPresenterAuthors
     *
     * @param string $language Language
     * @param array  $expected Result to be expected
     *
     * @return void
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('getNonPresenterAuthorsData')]
    public function testNonPresenterAuthors(
        string $language,
        array $expected
    ): void {
        $translator = $this
            ->getMockBuilder(\Laminas\I18n\Translator\Translator::class)
            ->disableOriginalConstructor()
            ->onlyMethods([])
            ->getMock();
        $translator->setLocale($language);
        $driver = $this->getInstitutionalRepositoryDriver();
        $driver->setTranslator($translator);
        $this->assertSame(
            $expected,
            $driver->getNonPresenterAuthors()
        );
    }

    /**
     * Test getXML
     *
     * @return void
     */
    public function testGetXml(): void
    {
        $this->assertXmlStringEqualsXmlString(
            $this->getFixture('qdc/qdc_ir_test.xml', 'Finna'),
            $this->getInstitutionalRepositoryDriver()->getXML('oai_qdc')
        );
    }

    /**
     * Test getAllImages with thumbail only.
     *
     * @return void
     */
    public function testGetThumbnailImages(): void
    {
        $driver = new SolrQdc([], [], new \VuFind\Config\Config([]));
        $driver->setRawData(
            [
                'id' => '1',
                'fullrecord' => $this->getFixture('qdc/qdc_thumbnail.xml', 'Finna'),
            ]
        );
        $this->assertSame(
            [
                [
                    'urls' => [
                        'large' => 'https://localhost/thumb.jpg',
                        'small' => 'https://localhost/thumb.jpg',
                        'medium' => 'https://localhost/thumb.jpg',
                    ],
                    'description' => '',
                    'rights' => [
                        'copyright' => '',
                        'link' => '',
                        'description' => [],
                    ],
                    'cacheSizes' => [
                        'medium' => 'small',
                    ],
                    'downloadable' => false,
                ],
            ],
            $driver->getAllImages()
        );
    }

    /**
     * Test getAllImages with PDF only.
     *
     * @return void
     */
    public function testGetAllImagesPdf(): void
    {
        $driver = $this->getInstitutionalRepositoryDriver(fixture: 'qdc/qdc_kk_pdf.xml');
        $this->assertSame(
            [
                [
                    'urls' => [
                        'large' => 'https://www.animals.of.earth.fi/duck.pdf',
                        'small' => 'https://www.animals.of.earth.fi/duck.pdf',
                        'medium' => 'https://www.animals.of.earth.fi/duck.pdf',
                    ],
                    'description' => '',
                    'rights' => [
                        'copyright' => '',
                        'link' => '',
                        'description' => [],
                    ],
                    'pdf' => true,
                    'cacheSizes' => [
                        'medium' => 'small',
                    ],
                    'downloadable' => true,
                ],
            ],
            $driver->getAllImages()
        );
        $this->assertSame(
            [],
            $driver->getAllImages(includePdf: false)
        );
    }

    /**
     * Function to get expected function data
     *
     * @return array
     */
    public static function getTestMuseumRecordFunctionsData(): array
    {
        return [
            [
                'getAllImages',
                [
                    0 => [
                        'urls' => [
                            'large' => 'https://www.savanni.art.collection.org/large/ducksinharmony.jpg',
                            'medium' => 'https://www.savanni.art.collection.org/medium/ducksinharmony.jpg',
                            'small' => 'https://www.savanni.art.collection.org/square/ducksinharmony.jpg',
                            'original' => 'https://www.savanni.art.collection.org/original/ducksinharmony.jpg',
                        ],
                        'description' => '',
                        'rights' => [
                            'copyright' => 'In CopyRight',
                            'link' => false,
                            'description' => [
                                '2023 Finna qa',
                            ],
                        ],
                        'highResolution' => [
                            'original' => [
                                0 => [
                                    'data' => [],
                                    'url' => 'https://www.savanni.art.collection.org/original/ducksinharmony.jpg',
                                    'format' => 'jpg',
                                ],
                            ],
                        ],
                        'cacheSizes' => [],
                        'downloadable' => false,
                    ],
                ],
            ],
            [
                'getAllRecordLinks',
                [
                    0 => [
                        'value' => 'Ducks in the universe',
                        'link' => [
                            'value' => 'Ducks in the universe',
                            'type' => 'allFields',
                        ],
                    ],
                ],
            ],
            [
                'getSeries',
                [],
            ],
            [
                'getIdentifier',
                [
                    0 => 'TM 1234',
                ],
            ],
            [
                'getKeywords',
                [],
            ],
            [
                'getISBNs',
                [],
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
                        'detail' => 'wikidata:P217',
                    ],
                ],
            ],
            [
                'getURLs',
                [],
            ],
            [
                'getEducationPrograms',
                [],
            ],
            [
                'getPhysicalDescriptions',
                [
                    0 => '2.1 cm x 2.3 cm',
                ],
            ],
            [
                'getPhysicalMediums',
                [
                    0 => 'Akryyli',
                    1 => 'Kangas',
                ],
            ],
            [
                'getDescriptions',
                [
                    0 => 'painting by Juha Kuoma',
                    1 => 'abstract should be removed',
                ],
            ],
            [
                'getAbstracts',
                [
                    '' => 'Ei suodatetussa',
                ],
            ],
            [
                'getDescriptionURL',
                false,
            ],
        ];
    }

    /**
     * Test functions with return value array
     *
     * @param string $function Function of the driver to test
     * @param mixed  $expected Result to be expected
     *
     * @return void
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('getTestMuseumRecordFunctionsData')]
    public function testMuseumRecordFunctions(
        string $function,
        $expected
    ): void {
        $driver = $this->getMuseumDriver();
        $this->assertTrue(is_callable([$driver, $function], true));
        $this->assertSame(
            $expected,
            $driver->$function()
        );
    }

    /**
     * Function to get expected publication date range data
     *
     * @return array
     */
    public static function getPublicationDateRangeData(): array
    {
        return [
            [
                '[2001-01-01 TO 2001-12-31]',
                ['2001'],
            ],
            [
                '[1998-01-01 TO 2012-12-31]',
                ['1998', '2012'],
            ],
            [
                '[-2002-01-01 TO 0100-12-31]',
                ['-2002', '100'],
            ],
            [
                '[-0099-10-31 TO -0001-05-01]',
                ['-99', '-1'],
            ],
            [
                '[0000-01-01 TO 0000-12-31]',
                ['0'],
            ],
            [
                '[0999-06-02 TO 9999-12-31]',
                ['999', ''],
            ],
            [
                '[-9999-01-01 TO 9998-12-31]',
                ['-9999', '9998'],
            ],
            [
                '1937-12-08',
                ['1937'],
            ],
            [
                '',
                null,
            ],
        ];
    }

    /**
     * Test getPublicationDateRange
     *
     * @param string $indexValue Index value to test
     * @param ?array $expected   Result to be expected
     *
     * @return void
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('getPublicationDateRangeData')]
    public function testGetPublicationDateRange(
        string $indexValue,
        ?array $expected
    ): void {
        $record = new SolrQdc(
            [],
            [],
            new \VuFind\Config\Config([])
        );
        $record->setRawData(
            [
                'id' => 'knp-247394',
                'publication_daterange' => $indexValue,
            ]
        );
        $this->assertSame(
            $expected,
            $record->getPublicationDateRange()
        );
    }

    /**
     * Simple function to test element filtering works properly
     *
     * @return void
     */
    public function testXmlElementFilter(): void
    {
        $driver = $this->getInstitutionalRepositoryDriver(fixture: 'qdc/qdc_kk.xml');
        $this->assertXmlStringEqualsXmlString(
            $this->getFixture('qdc/qdc_kk_filtered.xml', 'Finna'),
            $driver->getFilteredXML()
        );

        $driver = $this->getInstitutionalRepositoryDriver(fixture: 'qdc/qdc_museum_test.xml');
        $this->assertXmlStringEqualsXmlString(
            $this->getFixture('qdc/qdc_museum_test_filtered.xml', 'Finna'),
            $driver->getFilteredXML()
        );
    }

    /**
     * Function to get expected human readable publication dates data
     *
     * @return array
     */
    public static function getHumanReadablePublicationDatesData(): array
    {
        return [
            [
                '[2001-01-01 TO 2001-12-31]',
                ['2001'],
            ],
            [
                '[1998-01-01 TO 2012-12-31]',
                ['1998–2012'],
            ],
            [
                '[-2002-01-01 TO 0100-12-31]',
                ['-2002–100'],
            ],
            [
                '[-0099-10-31 TO -0001-05-01]',
                ['-99–-1'],
            ],
            [
                '[0000-01-01 TO 0000-12-31]',
                ['0'],
            ],
            [
                '[0999-06-02 TO 9999-12-31]',
                ['999–'],
            ],
            [
                '[-9999-01-01 TO 9998-12-31]',
                ['-9999–9998'],
            ],
            [
                '1937-12-08',
                ['1937'],
            ],
            [
                '',
                [],
            ],
        ];
    }

    /**
     * Test getHumanReadablePublicationDates
     *
     * @param string $indexValue Index value to test
     * @param ?array $expected   Result to be expected
     *
     * @return void
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('getHumanReadablePublicationDatesData')]
    public function testGetHumanReadablePublicationDates(
        string $indexValue,
        ?array $expected
    ): void {
        $record = new SolrQdc(
            [],
            [],
            new \VuFind\Config\Config([])
        );
        $record->setRawData(
            [
                'id' => 'knp-247394',
                'publication_daterange' => $indexValue,
            ]
        );
        $this->assertSame(
            $expected,
            $record->getHumanReadablePublicationDates()
        );
    }

    /**
     * Get a record driver with fake data
     *
     * @param array  $overrides    Fixture fields to override
     * @param array  $searchConfig Search configuration
     * @param string $fixture      Fixture file name
     *
     * @return SolrQdc
     */
    protected function getInstitutionalRepositoryDriver(
        $overrides = [],
        $searchConfig = [],
        string $fixture = 'qdc/qdc_ir_test.xml'
    ): SolrQdc {
        $fullRecord = $this->getFixture($fixture, 'Finna');
        $config = [
            'Content' => [
                'pdfCoverImageDownload' => '0/Painting',
            ],
            'Record' => [
                'allowed_external_hosts_mode' => 'disable',
                'disallowed_external_hosts' => [
                    'www.animals.of.moon.fi',
                ],
                'disallowed_external_hosts_mode' => 'enforce',
            ],
            'ImageRights' => [
                'fi' => [
                    'CC BY 4.0' => 'http://creativecommons.org/licenses/by/4.0/deed.fi',
                ],
                'en-gb' => [
                    'CC BY 4.0' => 'http://creativecommons.org/licenses/by/4.0/deed.en',
                ],
                'sv' => [
                    'CC BY 4.0' => 'http://creativecommons.org/licenses/by/4.0/deed.sv',
                ],
            ],
            'FileDownload' => [
                'excludeRights' => [
                    'InC',
                ],
            ],
        ];
        $config = new \VuFind\Config\Config($config);
        $record = new SolrQdc(
            $config,
            $config,
            new \VuFind\Config\Config($searchConfig)
        );
        $localeConfig = [
            'Site' => [
                'language' => 'fi',
                'fallback_languages' => 'en-gb,sv',
                'browserDetectLanguage' => false,
            ],
            'Languages' => [
                'fi' => 'Finnish',
                'en' => 'English',
                'sv' => 'Swedish',
                'en-gb' => 'British English',
            ],
        ];
        $localeConfig = new \VuFind\Config\Config($localeConfig);
        $record->attachLocaleSettings(new \VuFind\I18n\Locale\LocaleSettings($localeConfig));
        $record->setRawData(
            [
                'id' => 'knp-247394',
                'fullrecord' => $fullRecord,
                'usage_rights_str_mv' => [
                    'usage_A',
                ],
                'format' => '0/Painting',
                'author' => 'Kiira Kirjoittaja',
                'title_alt' => [
                    'Alt Title',
                ],
                'url' => [
                    'http://localhost/url1',
                    'http://localhost/url2',
                ],
            ]
        );
        return $record;
    }

    /**
     * Get a record driver with fake data
     *
     * @param array $overrides    Fixture fields to override
     * @param array $searchConfig Search configuration
     *
     * @return SolrQdc
     */
    protected function getMuseumDriver($overrides = [], $searchConfig = []): SolrQdc
    {
        $fixture = $this->getFixture('qdc/qdc_museum_test.xml', 'Finna');
        $config = [
            'Record' => [
                'allowed_external_hosts_mode' => 'disable',
            ],
            'ImageRights' => [
                'fi' => [
                    'CC BY 4.0' => 'http://creativecommons.org/licenses/by/4.0/deed.fi',
                ],
                'en-gb' => [
                    'CC BY 4.0' => 'http://creativecommons.org/licenses/by/4.0/deed.en',
                ],
                'sv' => [
                    'CC BY 4.0' => 'http://creativecommons.org/licenses/by/4.0/deed.sv',
                ],
            ],
        ];
        $record = new SolrQdc(
            $config,
            $config,
            new \VuFind\Config\Config($searchConfig)
        );
        $localeConfig = [
            'Site' => [
                'language' => 'fi',
                'fallback_languages' => 'en-gb,sv',
                'browserDetectLanguage' => false,
            ],
            'Languages' => [
                'fi' => 'Finnish',
                'en' => 'English',
                'sv' => 'Swedish',
                'en-gb' => 'British English',
            ],
        ];
        $localeConfig = new \VuFind\Config\Config($localeConfig);
        $record->attachLocaleSettings(new \VuFind\I18n\Locale\LocaleSettings($localeConfig));
        $record->setRawData(['id' => 'knp-247394', 'fullrecord' => $fixture]);
        return $record;
    }
}
