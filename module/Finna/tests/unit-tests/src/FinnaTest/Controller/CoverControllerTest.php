<?php

/**
 * CoverController test class
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
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301  USA
 *
 * @category VuFind
 * @package  Tests
 * @author   Juha Luoma <juha.luoma@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:testing:unit_tests Wiki
 */

namespace FinnaTest\Controller;

use Finna\Controller\CoverController;
use Finna\Cover\Loader;
use Finna\Db\Service\AccessTokenService;
use Finna\File\Loader as FileLoader;
use Finna\RecordDriver\SolrLido;
use Generator;
use Laminas\Db\ResultSet\ResultSet;
use Laminas\Http\Headers;
use Laminas\Http\PhpEnvironment\Response;
use Laminas\Stdlib\Parameters;
use PHPUnit\Framework\MockObject\MockObject;
use VuFind\Config\Config;
use VuFind\Cover\CachingProxy;
use VuFind\Db\Row\AccessToken as RowAccessToken;
use VuFind\Db\Table\AccessToken;
use VuFind\Http\PhpEnvironment\Request;
use VuFind\Record\Loader as RecordLoader;
use VuFind\RecordDriver\Missing;
use VuFind\Session\Settings;
use VuFindTest\Feature\FixtureTrait;

use function in_array;

/**
 * CoverController test class
 *
 * @category VuFind
 * @package  Tests
 * @author   Juha Luoma <juha.luoma@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:testing:unit_tests Wiki
 */
class CoverControllerTest extends \PHPUnit\Framework\TestCase
{
    use FixtureTrait;

    /**
     * Mock container
     *
     * @var \VuFindTest\Container\MockContainer
     */
    protected $container;

    /**
     * Database containing access tokens
     *
     * @var array
     */
    protected array $accessTokenDb = [
      [
        'id' => 1,
        'type' => 'access_token_other',
        'user_id' => 1,
        'created' => '2020-01-01 00:00:00',
        'data' => [
          'something' => 'else',
        ],
        'revoked' => 0,
      ],
      [
        'id' => 2,
        'type' => 'api_key',
        'user_id' => 2,
        'created' => '2020-01-01 00:00:00',
        'data' => 'test_key_123',
        'revoked' => 0,
      ],
      [
        'id' => 3,
        'type' => 'api_key',
        'user_id' => 3,
        'created' => '2020-01-01 00:00:00',
        'data' => 'not_going_to_work_123',
        'revoked' => 1,
      ],
    ];

    /**
     * Standard setup method.
     *
     * @return void
     */
    public function setUp(): void
    {
        $this->container = new \VuFindTest\Container\MockContainer($this);
    }

    /**
     * Data provider for testing image piping
     *
     * @return Generator
     */
    public static function getTestImagePipedData(): Generator
    {
        $configWithKeys = [
          'Content' => [
            'api_keys' => [
              'test_key_123',
            ],
          ],
        ];
        $datasourceConfigPiped = [
          'test' => [
            'permissions' => [
              'image_piping' => true,
            ],
          ],
        ];
        $requestWithApiKey = [
          'headers' => [
            'X-API-KEY' => 'test_key_123',
          ],
          'query' => [
            'id' => 'test.123',
            'source' => DEFAULT_SEARCH_BACKEND,
            'size' => 'large',
            'index' => 0,
          ],
        ];
        $requestWithApiKeyAndWrongIndex = [
          'headers' => [
            'X-API-KEY' => 'test_key_123',
          ],
          'query' => [
            'id' => 'test.123',
            'source' => DEFAULT_SEARCH_BACKEND,
            'size' => 'large',
            'index' => 10,
          ],
        ];
        $requestWithWrongApiKey = [
          'headers' => [
            'X-API-KEY' => 'not_going_to_work_123',
          ],
          'query' => [
            'id' => 'test.123',
            'source' => DEFAULT_SEARCH_BACKEND,
            'size' => 'large',
            'index' => 1,
          ],
        ];
        $datasourceConfigUnPiped = [
          'test' => [
            'permissions' => [
              'image_piping' => false,
            ],
          ],
        ];
        $requestWithoutApiKey = [
          'headers' => [

          ],
          'query' => [
            'id' => 'very_record.123',
            'source' => DEFAULT_SEARCH_BACKEND,
            'size' => 'large',
            'index' => 0,
          ],
        ];
        $requestWithMissingRecord = [
          'headers' => [
            'X-API-KEY' => 'test_key_123',
          ],
          'query' => [
            'id' => 'test.missing',
            'source' => DEFAULT_SEARCH_BACKEND,
            'size' => 'large',
            'index' => 0,
          ],
        ];
        $expectedResponse = new Response();
        $expectedResponse->setStatusCode(Response::STATUS_CODE_200);
        yield 'test with success' => [
          $configWithKeys,
          $datasourceConfigPiped,
          $requestWithApiKey,
          $expectedResponse,
        ];

        $expectedResponse = new Response();
        $expectedResponse->setStatusCode(Response::STATUS_CODE_401);
        yield 'test with wrong api key in request' => [
          $configWithKeys,
          $datasourceConfigPiped,
          $requestWithWrongApiKey,
          $expectedResponse,
        ];

        $expectedResponse = new Response();
        $expectedResponse->setStatusCode(Response::STATUS_CODE_401);
        yield 'test with no api key in request' => [
          $configWithKeys,
          $datasourceConfigPiped,
          $requestWithoutApiKey,
          $expectedResponse,
        ];

        $expectedResponse = new Response();
        $expectedResponse->setStatusCode(Response::STATUS_CODE_403);
        yield 'test with no permission to pipe image but has api key' => [
          $configWithKeys,
          $datasourceConfigUnPiped,
          $requestWithApiKey,
          $expectedResponse,
        ];

        $expectedResponse = new Response();
        $expectedResponse->setStatusCode(Response::STATUS_CODE_401);
        yield 'test with no permission to pipe image and no api key' => [
          $configWithKeys,
          $datasourceConfigUnPiped,
          $requestWithoutApiKey,
          $expectedResponse,
        ];

        $expectedResponse = new Response();
        $expectedResponse->setStatusCode(Response::STATUS_CODE_404);
        yield 'test with api key and permission but wrong index' => [
          $configWithKeys,
          $datasourceConfigPiped,
          $requestWithApiKeyAndWrongIndex,
          $expectedResponse,
        ];

        $expectedResponse = new Response();
        $expectedResponse->setStatusCode(Response::STATUS_CODE_404);
        yield 'test with missing record' => [
          $configWithKeys,
          $datasourceConfigPiped,
          $requestWithMissingRecord,
          $expectedResponse,
        ];
    }

    /**
     * Test pipe functionality
     *
     * @param array    $config           Main config
     * @param array    $datasourceConfig Datasource config
     * @param array    $params           Parameters for the action
     * @param Response $expected         Expected result
     *
     * @return       void
     * @dataProvider getTestImagePipedData
     */
    public function testImagePiped(array $config, array $datasourceConfig, array $params, Response $expected): void
    {
        $coverController = $this->getCoverController($config, $datasourceConfig, $params);
        $result = $coverController->pipeAction();
        $this->assertEquals($expected, $result);
    }

    /**
     * Get cover controller for testing
     *
     * @param array $config           Main config
     * @param array $datasourceConfig Datasource config
     * @param array $params           Test params for requesting image
     *
     * @return CoverController
     */
    protected function getCoverController(
        array $config = [],
        array $datasourceConfig = [],
        array $params = []
    ): CoverController {
        $testRequest = new Request();
        $headers = new Headers();
        $headers->addHeaders($params['headers'] ?? []);
        $testRequest->setHeaders($headers);
        $query = new Parameters($params['query'] ?? []);
        $testRequest->setQuery($query);

        $mockedParams = $this->container->createMock(
            \Laminas\Mvc\Controller\Plugin\Params::class,
            ['fromHeader', 'fromQuery']
        );
        $mockedParams->expects($this->any())->method('fromHeader')->willReturnCallback(
            function ($header, $default = null) use ($testRequest) {
                return $testRequest->getHeaders($header, $default);
            }
        );
        $mockedParams->expects($this->any())->method('fromQuery')->willReturnCallback(
            function ($query, $default = null) use ($testRequest) {
                return $testRequest->getQuery($query, $default);
            }
        );
        $fileLoader = $this->container->createMock(FileLoader::class, ['proxyFileLoad']);
        $fileLoader->expects($this->any())->method('proxyFileLoad')->willReturn(true);

        $coverControllerMock = $this->getMockBuilder(CoverController::class)
          ->onlyMethods(['__call'])->setConstructorArgs([
            $this->container->createMock(Loader::class),
            $this->container->createMock(CachingProxy::class),
            $this->container->createMock(Settings::class),
            new Config($datasourceConfig),
            $this->getMockedRecordLoader(),
            $config['Content'] ?? [],
            $fileLoader,
            $this->getMockedAccessTokenService(),
          ])->getMock();
        $coverControllerMock->expects($this->any())->method('__call')->with('params')->willReturn($mockedParams);
        return $coverControllerMock;
    }

    /**
     * Get mocked database for testing API-keys
     *
     * @return MockObject
     */
    protected function getMockedAccessTokenService(): MockObject
    {

        $accessTokenService = $this->container->createMock(AccessTokenService::class, ['getDbTable']);
        $accessTokenRow = $this->container->createMock(RowAccessToken::class, ['isRevoked']);
        $resultSetRow = $this->container->createMock(ResultSet::class, ['current']);

        $accessTokens = $this->accessTokenDb;

        $dbTable = $this->container->createMock(AccessToken::class, ['select']);
        $dbTable->expects($this->any())->method('select')->willReturnCallback(
            function ($query) use ($accessTokens, $accessTokenRow, $resultSetRow) {
                $foundTokens = [];
                foreach ($accessTokens as $token) {
                    if ($token['type'] === $query['type'] && $token['data'] === $query['data']) {
                        $clonedToken = clone $accessTokenRow;
                        $clonedToken->expects($this->any())->method('isRevoked')->willReturn(!!$token['revoked']);
                        $foundTokens[] = $clonedToken;
                    }
                }
                $resultSetRow->expects($this->any())->method('current')->willReturn($foundTokens[0] ?? null);
                return $resultSetRow;
            }
        );

        $accessTokenService->expects($this->any())->method('getDbTable')->willReturn($dbTable);
        return $accessTokenService;
    }

    /**
     * Get mocked record loader for loading records
     *
     * @param array $missingRecords List of ids which should return Missing record object
     *
     * @return MockObject
     */
    protected function getMockedRecordLoader(array $missingRecords = ['test.missing']): MockObject
    {
        $recordLoader = $this->container->createMock(RecordLoader::class, ['load']);
        $recordLoader->expects($this->any())->method('load')->willReturnCallback(
            function ($id, $source) use ($missingRecords) {
                if (in_array($id, $missingRecords)) {
                    $mockedMissing = $this->container->createMock(Missing::class, []);
                    return $mockedMissing;
                }
                $fixture = $this->getFixture('lido/lido_test.xml', 'Finna');
                $record = new SolrLido();
                $splitted = explode('.', $id, 2);
                $record->setRawData([
                  'datasource_str_mv' => [
                    $splitted[0],
                  ],
                  'id' => $id,
                  'fullrecord' => $fixture,
                ]);
                return $record;
            }
        );
        return $recordLoader;
    }
}
