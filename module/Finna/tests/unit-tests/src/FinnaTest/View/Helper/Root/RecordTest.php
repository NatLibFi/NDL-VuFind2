<?php

/**
 * Record Helper Test Class.
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
 * along with this program; if not, see
 * <https://www.gnu.org/licenses/>.
 *
 * @category VuFind
 * @package  Tests
 * @author   Juha Luoma <juha.luoma@helsinki.fi>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:testing:unit_tests Wiki
 */

namespace FinnaTest\View\Helper\Root;

use Finna\View\Helper\Root\Record;
use Finna\View\Helper\Root\RecordLinker;
use Finna\View\Helper\Root\TruncateUrl;
use Laminas\View\Renderer\PhpRenderer;
use PHPUnit\Framework\MockObject\MockObject;
use VuFind\RecordDriver\DefaultRecord;
use VuFind\View\Helper\Root\Context;
use VuFind\View\Helper\Root\OpenUrl;
use VuFindTest\Feature\FixtureTrait;

/**
 * Record Helper Test Class.
 *
 * @category VuFind
 * @package  Tests
 * @author   Juha Luoma <juha.luoma@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:testing:unit_tests Wiki
 */
class RecordTest extends \PHPUnit\Framework\TestCase
{
    use FixtureTrait;

    /**
     * Test getLinkDetailsExtended function.
     *
     * @return void
     */
    public function testGetLinkDetailsExtended(): void
    {
        $testFixture = $this->getJsonFixture('helpers/record_media_data.json', 'Finna');
        $expectedData = $this->getJsonFixture('helpers/record_media_expected.json', 'Finna');
        foreach ($testFixture as $message => $testData) {
            $testData['onlineURLs'] = array_map(
                fn ($url) => json_encode($url),
                $testData['onlineURLs']
            );
            $driver = $this->getDriver(
                $testData['onlineURLs'],
                $testData['mergedData'],
                $testData['iiifManifests']
            );
            $recordHelper = $this->getRecordHelper(
                true,
                $testData['urls']
            );
            $expected = $expectedData[$message];
            $message = "getLinkDetails: $message";

            $linkDetails = ($recordHelper)($driver)->getLinkDetailsExtended();
            $this->assertEquals($expected, $linkDetails, $message);
        }
    }

    /**
     * Get mocked driver object.
     *
     * @param array $onlineURLs    Response for function getOnlineURLs
     * @param array $mergedData    Response for function getMergedRecordData
     * @param array $iiifManifests Response for function getIiifManifests
     *
     * @return MockObject
     */
    protected function getDriver(
        array $onlineURLs,
        array $mergedData,
        array $iiifManifests
    ): MockObject {
        $tryMethodMap = [
            ['getOnlineURLs', [], [], $onlineURLs],
            ['getMergedRecordData', [], [], $mergedData],
            ['getIiifManifests', [], [], $iiifManifests],
        ];
        $driver = $this->createMock(DefaultRecord::class);
        $driver->method('tryMethod')
            ->willReturnMap($tryMethodMap);
        return $driver;
    }

    /**
     * Get record helper object.
     *
     * @param bool  $openUrlActive  Is the openUrl active?
     * @param array $getLinkDetails Result for Record helpers function getLinkDetails
     *
     * @return MockObject
     */
    protected function getRecordHelper(
        bool $openUrlActive = true,
        array $getLinkDetails = [],
    ): MockObject {
        $openURLPlugin = $this->createPartialMock(OpenUrl::class, ['__invoke', 'isActive']);
        $openURLPlugin->method('isActive')->willReturn(true);
        $openURLPlugin->expects($this->once())->method('__invoke')->willReturn($openURLPlugin);

        $contextHelper = $this->createPartialMock(Context::class, ['__invoke']);

        $truncateUrlPlugin = new TruncateUrl();

        $recordLinkerPlugin = $this->createPartialMock(RecordLinker::class, []);
        $pluginMap = [
            ['openUrl', $openURLPlugin],
            ['truncateUrl', $truncateUrlPlugin],
            ['recordLinker', $recordLinkerPlugin],
            ['context', $contextHelper],
        ];
        $viewMock = $this->createMock(PhpRenderer::class);
        $viewMock->method('plugin')->willReturnMap($pluginMap);

        $recordHelper = $this->getMockBuilder(Record::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getLinkDetails', 'getView'])->getMock();

        $recordHelper->method('getLinkDetails')->willReturn($getLinkDetails);
        $recordHelper->method('getView')->willReturn($viewMock);
        return $recordHelper;
    }
}
