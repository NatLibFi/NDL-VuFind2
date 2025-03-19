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
use Finna\Controller\CoverControllerFactory;
use Finna\Cover\Loader;
use Finna\File\Loader as FileLoader;
use Finna\RecordDriver\SolrLido;
use Generator;
use Laminas\Http\Headers;
use Laminas\Http\PhpEnvironment\Response;
use Laminas\Stdlib\Parameters;
use VuFind\Config\Config;
use VuFind\Config\PluginManager;
use VuFind\Cover\CachingProxy;
use VuFind\Cover\Loader as CoverLoader;
use VuFind\Http\PhpEnvironment\Request;
use VuFind\Record\Loader as RecordLoader;
use VuFind\RecordDriver\Missing;
use VuFind\Session\Settings;
use VuFindTest\Container\MockContainer;
use VuFindTest\Feature\FixtureTrait;

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
     * Standard setup method.
     *
     * @return void
     */
    public function setUp(): void
    {
        $this->container = new \VuFindTest\Container\MockContainer($this);
        $coverLoader = $this->container->createMock(Loader::class);
        $this->container->set(\VuFind\Cover\Loader::class, $coverLoader);
        $cachingProxy = $this->container->createMock(CachingProxy::class);
        $this->container->set(CachingProxy::class, $cachingProxy);
        $sessionSettings = $this->container->createMock(Settings::class);
        $this->container->set(Settings::class, $sessionSettings);
        $this->container->set(PluginManager::class, $this->container);

        $imagesResponseMocked = [
          [
            'urls' => [
              'large' => 'https://largekuvanlinkki.com',
              'small' => 'https://largekuvanlinkki.com',
              'medium' => 'https://largekuvanlinkki.com',
            ],
          ],
        ];

        $recordLoader = $this->container->createMock(RecordLoader::class, ['load']);
        $recordLoader->expects($this->any())->method('load')->willReturnCallback(
            function ($id, $source) use ($imagesResponseMocked) {
                if ($id === 'test.missing') {
                    $mockedMissing = $this->container->createMock(Missing::class, []);
                    return $mockedMissing;
                }
                $mockRecord = $this->container->createMock(
                    SolrLido::class,
                    ['getDatasource', 'getAllImages', 'getUniqueID', 'getIdentifiersByType']
                );
                $splitted = explode('.', $id, 2);
                $mockRecord->expects($this->any())->method('getDatasource')->willReturn($splitted[0]);
                $mockRecord->expects($this->any())->method('getAllImages')->willReturn($imagesResponseMocked);
                $mockRecord->expects($this->any())->method('getUniqueID')->willReturn($id);
                $mockRecord->expects($this->any())->method('getIdentifiersByType')->willReturn([]);
                return $mockRecord;
            }
        );
        $this->container->set(RecordLoader::class, $recordLoader);
        $fileLoader = $this->container->createMock(FileLoader::class, ['proxyFileLoad']);
        $fileLoader->expects($this->any())->method('proxyFileLoad')->willReturn(true);
        $this->container->set(FileLoader::class, $fileLoader);
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
            'allow_image_piping' => true,
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
            'index' => 1,
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
            'something_here' => true,
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
        $expectedResponse->setStatusCode(Response::STATUS_CODE_401);
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
        $coverController = $this->getCoverController($config, $datasourceConfig);
        $testRequest = new Request();
        $headers = new Headers();
        $headers->addHeaders($params['headers'] ?? []);
        $testRequest->setHeaders($headers);
        $query = new Parameters($params['query'] ?? []);
        $testRequest->setQuery($query);
        $coverController->setRequest($testRequest);
        $result = $coverController->pipeAction($testRequest);
        $this->assertEquals($expected, $result);
    }

    /**
     * Get cover controller for testing
     *
     * @param array $config           Main config
     * @param array $datasourceConfig Datasource config
     *
     * @return CoverController
     */
    protected function getCoverController(array $config = [], array $datasourceConfig = []): CoverController
    {
        $factory = new CoverControllerFactory();
        $this->container->set('config', new Config($config));
        $this->container->set('datasources', new Config($datasourceConfig));
        $mockedParamsPlugin = new class () {
            /**
             * Request object
             *
             * @var Request
             */
            protected Request $request;

            /**
             * Init
             *
             * @param Request $request Request to set
             *
             * @return void
             */
            public function init(Request $request)
            {
                $this->request = $request;
            }

            /**
             * Proxy fromQuery
             *
             * @param ?string $param   Param to get
             * @param mixed   $default Default to return
             *
             * @return mixed
             */
            public function fromQuery($param = null, mixed $default = null)
            {
                if (null !== $param) {
                    return $this->request->getQuery($param, $default);
                }
                return $this->request->getQuery();
            }

            /**
             * Proxy fromPost
             *
             * @param ?string $param   Param to get
             * @param mixed   $default Default to return
             *
             * @return mixed
             */
            public function fromPost($param = null, mixed $default = null)
            {
                if (null !== $param) {
                    return $this->request->getPost($param, $default);
                }
                return $this->request->getPost();
            }

            /**
             * Proxy fromHeader
             *
             * @param ?string $param   Param to get
             * @param mixed   $default Default to return
             *
             * @return mixed
             */
            public function fromHeader($param = null, mixed $default = null)
            {
                if (null !== $param) {
                    return $this->request->getHeaders($param, $default);
                }
                return $this->request->getHeaders();
            }
        };
        $mockedCoverController = new class ($this->container, $mockedParamsPlugin) extends CoverController {
            /**
             * Constructor
             *
             * @param MockContainer $container    Mocked container
             * @param mixed         $mockedParams Mocked params plugin
             *
             * @return void
             */
            public function __construct(
                protected MockContainer $container,
                protected $mockedParams
            ) {
                parent::__construct(
                    $container->get(CoverLoader::class),
                    $container->get(CachingProxy::class),
                    $container->get(Settings::class),
                    $container->get('datasources'),
                    $container->get(RecordLoader::class),
                    $container->get('config')?->Content?->toArray() ?? [],
                    $container->get(FileLoader::class)
                );
            }

            /**
             * Set request
             *
             * @param Request $request Set request acting like user request
             *
             * @return void
             */
            public function setRequest(Request $request)
            {
                $this->request = $request;
                $this->mockedParams->init($request);
            }

            /**
             * Override $this->params to get mocked request to actually test for the function
             *
             * @return mixed
             */
            public function params()
            {
                return $this->mockedParams;
            }
        };
        return $mockedCoverController;
    }
}
