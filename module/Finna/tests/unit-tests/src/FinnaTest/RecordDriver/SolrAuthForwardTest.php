<?php

/**
 * SolrAuthForward Test Class
 *
 * PHP version 8
 *
 * Copyright (C) The National Library of Finland 2026.
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
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:testing:unit_tests Wiki
 */

namespace FinnaTest\RecordDriver;

use Finna\RecordDriver\SolrAuthForward;
use Finna\RecordDriver\SolrForward;
use Finna\Video\Handler\DefaultVideo;
use Finna\Video\Video;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * SolrAuthForward Record Driver Test Class
 *
 * @category VuFind
 * @package  Tests
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:testing:unit_tests Wiki
 */
class SolrAuthForwardTest extends \PHPUnit\Framework\TestCase
{
    use \VuFindTest\Feature\FixtureTrait;

    /**
     * Data provider for testPersonMethod.
     *
     * @return \Iterator
     */
    public static function personMethodProvider(): \Iterator
    {
        yield [
            'getAlternativeTitles',
            [
                'Markka Jala (oikea nimi)',
            ],
        ];
        yield [
            'getSummary',
            [
                'Nootti',
            ],
        ];
        yield [
            'getBirthDate',
            '27.09.1935',
        ];
        yield [
            'getDeathDate',
            '3.2.2026',
        ];
        yield [
            'getBirthPlace',
            'Valtasora',
        ];
        yield [
            'getDeathPlace',
            'Lahjo',
        ];
        yield [
            'getEstablishedDate',
            '',
        ];
        yield [
            'getTerminatedDate',
            '',
        ];
        yield [
            'getAwards',
            [
                '1. palkinto',
                '2. palkinto',
            ],
        ];
    }

    /**
     * Test retrieval methods for a person authority.
     *
     * @param string $method   Method
     * @param mixed  $expected Expected result
     *
     * @return void
     */
    #[DataProvider('personMethodProvider')]
    public function testPersonMethod(string $method, mixed $expected): void
    {
        $driver = $this->getDriver('forward/forward_auth_person_test.xml', 'Personal Name');
        $this->assertSame($expected, $driver->$method());
    }

    /**
     * Data provider for testCorporateMethod.
     *
     * @return \Iterator
     */
    public static function corporateMethodProvider(): \Iterator
    {
        yield [
            'getAlternativeTitles',
            [],
        ];
        yield [
            'getSummary',
            [
                'Valmistamon jutut',
            ],
        ];
        yield [
            'getBirthDate',
            '',
        ];
        yield [
            'getDeathDate',
            '',
        ];
        yield [
            'getBirthPlace',
            '',
        ];
        yield [
            'getDeathPlace',
            '',
        ];
        yield [
            'getEstablishedDate',
            '1.1.2020',
        ];
        yield [
            'getTerminatedDate',
            '9.2.2026',
        ];
        yield [
            'getAwards',
            [],
        ];
    }

    /**
     * Test retrieval methods for a corporate authority.
     *
     * @param string $method   Method
     * @param mixed  $expected Expected result
     *
     * @return void
     */
    #[DataProvider('corporateMethodProvider')]
    public function testCorporateMethod(string $method, mixed $expected): void
    {
        $driver = $this->getDriver('forward/forward_auth_corporate_test.xml', 'Corporate Name');
        $this->assertSame($expected, $driver->$method());
    }

    /**
     * Get a record driver with fake data.
     *
     * @param string $fixture Fixture
     * @param string $type    Authority type
     *
     * @return SolrForward
     */
    protected function getDriver(string $fixture, string $type): SolrAuthForward
    {
        $mainConfig = new \VuFind\Config\Config([
            'ImageRights' => [
                'fi' => [
                    'LUVANVARAINEN KÄYTTÖ / EI TIEDOSSA' => 'EI EI!',
                ],
            ],
        ]);
        $record = new SolrAuthForward(
            $mainConfig,
            null,
            new \VuFind\Config\Config([])
        );
        $mockHandler = $this->createMock(DefaultVideo::class);
        $mockHandler
            ->method('getData')
            ->willReturnCallback(
                function ($params) {
                    return $params;
                }
            );
        $mockVideo = $this->createMock(Video::class);
        $mockVideo
            ->method('getHandler')
            ->with('testsrc')
            ->willReturn($mockHandler);
        $record->attachVideoHandler($mockVideo);
        $record->setRawData(
            [
                'id' => 'test-id',
                'fullrecord' => $this->getFixture($fixture, 'Finna'),
                'source_str_mv' => [
                    'testsrc',
                ],
                'record_type' => $type,
                'birth_place' => 'Valtasora',
                'death_place' => 'Lahjo',
            ]
        );
        return $record;
    }
}
