<?php
/**
 * SolrEad3 Test Class
 *
 * PHP version 8
 *
 * Copyright (C) The National Library of Finland 2023.
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
 * @author   Jaro Ravila <jaro.ravila@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:testing:unit_tests Wiki
 */
namespace FinnaTest\RecordDriver;

use Finna\RecordDriver\SolrEad3;

/**
 * SolrEad3 Record Driver Test Class
 *
 * @category VuFind
 * @package  Tests
 * @author   Jaro Ravila <jaro.ravila@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:testing:unit_tests Wiki
 */
class SolrEad3Test extends \PHPUnit\Framework\TestCase
{
    use \VuFindTest\Feature\FixtureTrait;
    use \VuFindTest\Feature\TranslatorTrait;

    /**
     * Get unit dates
     *
     * @return void
     */
    public function testGetUnitDates()
    {
        $driver = $this->getDriver();
        $ndash = html_entity_decode('&#x2013;', ENT_NOQUOTES, 'UTF-8');
        $dates = [
            [
                'data' => "1600{$ndash}1799",
                'detail' => 'Ajallinen kattavuus'
            ],
            [
                'data' => "1661",
                'detail' => 'Ajallinen kattavuus',
            ],
            [
                'data' => "1660-luku",
                'detail' => 'Ajallinen kattavuus',
            ],
            [
                'data' => "01.01.1600{$ndash}01.01.1610",
                'detail' => 'Ajallinen kattavuus',
            ]
        ];
        $this->assertEquals($dates, $driver->getUnitDates());
    }

    /**
     * Get a record driver with fake data.
     *
     * @param array $overrides    Fixture fields to override.
     * @param array $searchConfig Search configuration.
     *
     * @return SolrEad3
     */
    protected function getDriver($overrides = [], $searchConfig = []): SolrEad3
    {
        $fixture = $this->getFixture('ead3/ead3_test.xml', 'Finna');
        $record = new SolrEad3(
            null,
            null,
            new \Laminas\Config\Config($searchConfig)
        );
        $record->setTranslator(
            $this->getMockTranslator(
                ['default' => ['year_decade_or_century' => '%%year%%-luku']]
            )
        );
        $record->setRawData(['fullrecord' => $fixture]);
        return $record;
    }
}
