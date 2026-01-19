<?php

/**
 * Unit tests for record formatter.
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
 * along with this program; if not, see
 * <https://www.gnu.org/licenses/>.
 *
 * @category VuFind
 * @package  Tests
 * @author   Juha Luoma <juha.luoma@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org
 */

namespace FinnaTest\Formatter;

use FinnaApi\Formatter\RecordFormatter;
use Generator;
use Laminas\View\HelperPluginManager;
use Symfony\Component\Yaml\Yaml;
use VuFind\View\Helper\Root\Translate;
use VuFindTest\Feature\FixtureTrait;

/**
 * Unit tests for record formatter.
 *
 * @category VuFind
 * @package  Tests
 * @author   Juha Luoma <juha.luoma@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org
 */
class RecordFormatterTest extends \PHPUnit\Framework\TestCase
{
    use FixtureTrait;

    /**
     * Get a helper plugin manager for the RecordFormatter.
     *
     * @return \Laminas\View\HelperPluginManager
     */
    protected function getHelperPluginManager()
    {
        $hm = $this->createMock(HelperPluginManager::class);
        $contextHelper = $this->getMockBuilder(\VuFind\View\Helper\Root\Context::class)
            ->disableOriginalConstructor()
            ->onlyMethods([])
            ->getMock();
        $hm->setService('context', $contextHelper);

        $viewMock = $this->getMockBuilder(\Laminas\View\Renderer\PhpRenderer::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['plugin'])
            ->getMock();
        $viewMock->method('plugin')->willReturnCallback(
            function ($name) use ($hm) {
                return $hm->get($name);
            }
        );
        $recordHelper = $this->getMockBuilder(\Finna\View\Helper\Root\Record::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getView'])
            ->getMock();
        $recordHelper->method('getView')->willReturn($viewMock);
        $translationEmpty = $this->createMock(\VuFind\View\Helper\Root\TranslationEmpty::class);
        $translationEmpty
            ->method('__invoke')
            ->willReturn(true);
        $recordLinker = $this->createMock(\VuFind\View\Helper\Root\RecordLinker::class);
        $recordLinker
            ->method('getUrl')
            ->willReturn('http://record.fi/record');

        $urlHelper = $this->getMockBuilder(\VuFind\View\Helper\Root\Url::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['__invoke'])
            ->getMock();
        $urlHelper
            ->method('__invoke')
            ->willReturn('/Cover/Show');
        $recordImage = $this->getMockBuilder(\Finna\View\Helper\Root\RecordImage::class)
            ->setConstructorArgs([$urlHelper])
            ->onlyMethods([])
            ->getMock();
        $hm
            ->method('get')
            ->willReturnMap(
                [
                    [
                        'recordImage',
                        null,
                        $recordImage,
                    ],
                    [
                        'record',
                        null,
                        $recordHelper,
                    ],
                    [
                        'translate',
                        null,
                        $this->createMock(Translate::class),
                    ],
                    [
                        'context',
                        null,
                        $contextHelper,
                    ],
                    [
                      'recordLinker',
                      null,
                      $recordLinker,
                    ],
                    [
                        'translationEmpty',
                        null,
                        $translationEmpty,
                    ],
                ]
            );
        return $hm;
    }

    /**
     * Get default field definitions.
     *
     * @return array
     */
    protected function getDefaultDefs(): array
    {
        // Use the real sample file to keep tests updated.
        return Yaml::parseFile(
            APPLICATION_PATH . '/local/config/finna/SearchApiRecordFields.yaml.sample'
        );
    }

    /**
     * Get a formatter to test with.
     *
     * @param array $defs Configuration for formatter
     *
     * @return RecordFormatter
     */
    protected function getFormatter(array $defs = []): RecordFormatter
    {
        if (!$defs) {
            $defs = $this->getDefaultDefs();
        }
        return new RecordFormatter(
            $defs,
            $this->getHelperPluginManager(),
            'fi'
        );
    }

    /**
     * Get a record driver to test with.
     *
     * @param string $indexFixture Fixture for indexed record
     * @param string $xmlFixture   Fixture for full XML record
     *
     * @return \VuFindTest\RecordDriver\TestHarness
     */
    protected function getDriver(string $indexFixture, string $xmlFixture)
    {
        $recordDriverClass = '';
        if (str_starts_with($indexFixture, 'marc/')) {
            $recordDriverClass = \Finna\RecordDriver\SolrMarc::class;
        } elseif (str_starts_with($indexFixture, 'lido/')) {
            $recordDriverClass = \Finna\RecordDriver\SolrLido::class;
        } else {
            throw new \Exception('Unknown record driver for fixture ' . $indexFixture);
        }
        $driver = $this->getMockBuilder($recordDriverClass)
            ->onlyMethods(['getDbService'])
            ->disableOriginalConstructor()
            ->getMock();
        $marcFields = $this->getJsonFixture(
            $indexFixture,
            'FinnaApi'
        );
        $marcFields['fullrecord'] = $this->getFixture(
            $xmlFixture,
            'FinnaApi'
        );
        $driver->setRawData($marcFields);

        $ratingsService = $this->getMockBuilder(\VuFind\Db\Service\RatingsService::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getRecordRatings'])
            ->getMock();
        $ratingsService
            ->method('getRecordRatings')
            ->willReturn([
                'count' => 5000,
                'rating' => 10,
            ]);
        $driver
            ->method('getDbService')
            ->willReturnMap(
                [
                    [
                        \VuFind\Db\Service\RatingsServiceInterface::class,
                        $ratingsService,
                    ],
                ]
            );
        return $driver;
    }

    /**
     * Data provider for testFormatter.
     *
     * @return Generator
     */
    public static function getTestRecordFormatterData(): Generator
    {
        yield 'Test marc record' => [
            'marc/record_formatter_test_1.json',
            'marc/record_formatter_test_1.xml',
            'marc/record_formatter_test_1_result.json',
        ];
        yield 'Test lido record' => [
            'lido/record_formatter_test_1.json',
            'lido/record_formatter_test_1.xml',
            'lido/record_formatter_test_1_result.json',
        ];
    }

    /**
     * Test the record formatter.
     *
     * @param string $indexFixture  Fixture for indexed record
     * @param string $xmlFixture    Fixture for full XML record
     * @param string $resultFixture Fixture for expected result
     *
     * @return void
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('getTestRecordFormatterData')]
    public function testFormatter(string $indexFixture, string $xmlFixture, string $resultFixture)
    {
        $formatter = $this->getFormatter();

        $driver = $this->getDriver($indexFixture, $xmlFixture);
        // Test requesting no fields.
        $this->assertEquals([], $formatter->format([$driver], []));

        // Test requesting fields:
        $results = $formatter->format(
            [$driver],
            array_keys($this->getDefaultDefs())
        );
        $expected = $this->getJsonFixture(
            $resultFixture,
            'FinnaApi'
        );
        $this->assertEquals($expected, $results);
    }

    /**
     * Test getting the field specs.
     *
     * @return void
     */
    public function testFieldSpecs()
    {
        $formatter = $this->getFormatter();
        $results = $formatter->getRecordFieldSpec();
        $expected = $this->getJsonFixture(
            'result/SearchApiRecordFields_result.json',
            'FinnaApi'
        );
        $this->assertEquals($expected, $results);
    }
}
