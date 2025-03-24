<?php

/**
 * SolrMarc Test Class
 *
 * PHP version 7
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
 * @link     https://vufind.org/wiki/development:testing:unit_tests Wiki
 */

namespace FinnaTest\RecordDriver;

use Finna\RecordDriver\SolrMarc;
use Generator;
use VuFind\XSLT\Import\VuFind;

/**
 * SolrMarc Record Driver Test Class
 *
 * @category VuFind
 * @package  Tests
 * @author   Juha Luoma <juha.luoma@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:testing:unit_tests Wiki
 */
class SolrMarcTest extends \PHPUnit\Framework\TestCase
{
    use \VuFindTest\Feature\FixtureTrait;

    /**
     * Data provider for testTitlePunctuation
     *
     * @return array
     */
    public static function getTestTitlePunctuationData(): array
    {
        return [
            [
                'Title',
                'Title /:',
            ],
            [
                'Title',
                'Title /  ',
            ],
            [
                'Title',
                'Title (((',
            ],
            [
                '(((',
                '(((',
            ],
        ];
    }

    /**
     * Test title trailing punctuation handling
     *
     * @param string $expected Expected result
     * @param string $title    Record title
     *
     * @dataProvider getTestTitlePunctuationData
     *
     * @return void
     */
    public function testTitlePunctuation(string $expected, string $title): void
    {
        $marc = [
            'leader' => '',
            'fields' => [
                [
                    '245' => [
                        'ind1' => ' ',
                        'ind2' => ' ',
                        'subfields' => [
                            ['a' => $title],
                        ],
                    ],
                ],
            ],
        ];

        $record = new SolrMarc();
        $record->setRawData(
            [
                'fullrecord' => json_encode($marc),
            ]
        );

        $this->assertEquals($expected, $record->getTitle());
    }

    /**
     * Data provider for testRecordLinking
     *
     * @return Generator
     */
    public static function getTestRecordLinkingData(): Generator
    {
        yield 'legacy record links' => [
            'marc/legacy_linking_ids.xml',
            [
                [
                    'id' => 'test.123456',
                    'linkingId' => '',
                    'sourceId' => 'Solr',
                    'title' => 'United records parent',
                    'reference' => '',
                    'publishingInfo' => '',
                    'mainHeading' => '',
                ],
                [
                    'id' => '',
                    'linkingId' => '',
                    'sourceId' => 'Solr',
                    'title' => 'United records parent',
                    'reference' => '',
                    'publishingInfo' => '',
                    'mainHeading' => '',
                ],
            ],
        ];
        yield 'record link' => [
            'marc/linking_ids.xml',
            [
                [
                    'id' => '',
                    'linkingId' => '(FI-MELINDA)123456789',
                    'sourceId' => 'Solr',
                    'title' => 'United records parent',
                    'reference' => '',
                    'publishingInfo' => '',
                    'mainHeading' => '',
                ],
            ],
        ];
    }

    /**
     * Test record linking with Legacy and new way
     *
     * @param string $fixture  Fixture path to test file
     * @param array  $expected Array of expected results
     *
     * @dataProvider getTestRecordLinkingData
     *
     * @return void
     */
    public function testGetHostRecords(string $fixture, array $expected): void
    {
        $xml = $this->getFixture($fixture, 'Finna');
        $record = new \VuFind\Marc\MarcReader($xml);
        $obj = $this->getMockBuilder(SolrMarc::class)
            ->onlyMethods(['getMarcReader'])->getMock();
        $obj->expects($this->any())->method('getMarcReader')->willReturn($record);
        $this->assertEquals($expected, $obj->getHostRecords());
    }

    /**
     * Data provider for testRecordLinking
     *
     * @return Generator
     */
    public static function getTestAllRecordLinksData(): Generator
    {
        yield 'legacy record links' => [
            'marc/legacy_linking_ids.xml',
            [
                [
                    'value' => 'United records parent',
                    'title' => 'United',
                    'link' => [
                        'type' => 'bib',
                        'value' => 'test.123456',
                    ],
                ],
                [
                    'value' => 'United records parent',
                    'title' => 'United',
                    'link' => [
                        'type' => 'title',
                        'value' => 'United records parent',
                    ],
                ],
            ],
        ];
        yield 'record link' => [
            'marc/linking_ids.xml',
            [
                [
                    'value' => 'United records parent',
                    'title' => 'United',
                    'link' => [
                        'type' => 'linkingId',
                        'value' => '(FI-MELINDA)123456789',
                    ],
                ],
            ],
        ];
    }

    /**
     * Test getAllRecordLinks
     *
     * @param string $fixture  Fixture path to test file
     * @param array  $expected Array of expected results
     *
     * @dataProvider getTestAllRecordLinksData
     *
     * @return void
     */
    public function testGetAllRecordLinks(string $fixture, array $expected): void
    {
        $xml = $this->getFixture($fixture, 'Finna');
        $record = new \VuFind\Marc\MarcReader($xml);
        $config = new \VuFind\Config\Config([
            'Record' => [
                'marc_links' => '760,762,765,767,770,772,773,775,776,780,785',
                'marc_links_link_types' => 'id,linkingId,oclc,dlc,isbn,issn,title',
            ],
        ]);
        $obj = $this->getMockBuilder(SolrMarc::class)
            ->onlyMethods(['getMarcReader'])->setConstructorArgs([$config, null, null])->getMock();
        $obj->expects($this->any())->method('getMarcReader')->willReturn($record);
        $this->assertEquals($expected, $obj->getAllRecordLinks());
    }
}
