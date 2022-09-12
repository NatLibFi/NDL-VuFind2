<?php
/**
 * Bazaar API Controller
 *
 * PHP version 7
 *
 * Copyright (C) The National Library of Finland 2021.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.    See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301  USA
 *
 * @category VuFind
 * @package  Controller
 * @author   Aida Luuppala <aida.luuppala@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:controllers Wiki
 */
namespace FinnaApi\Controller;

use VuFindApi\Controller\ApiController;
use VuFindApi\Controller\ApiInterface;
use VuFindApi\Controller\ApiTrait;

/**
 * Bazaar API Controller
 *
 * Controls the Bazaar API functionality
 *
 * @category VuFind
 * @package  Service
 * @author   Aida Luuppala <aida.luuppala@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:controllers Wiki
 */
class BazaarApiController extends ApiController implements ApiInterface
{
    use ApiTrait;

    /**
     * Execute the request
     *
     * @param \Laminas\Mvc\MvcEvent $e Event
     *
     * @return mixed
     * @throws Exception\DomainException
     */
    public function onDispatch(\Laminas\Mvc\MvcEvent $e)
    {
        // Add CORS headers and handle OPTIONS requests. This is a simplistic
        // approach since we allow any origin. For more complete CORS handling
        // a module like zfr-cors could be used.
        $response = $this->getResponse();
        $headers = $response->getHeaders();
        $headers->addHeaderLine('Access-Control-Allow-Origin', '*');
        $headers->addHeaderLine(
            'Access-Control-Allow-Methods',
            'GET, POST, OPTIONS'
        );
        $headers->addHeaderLine(
            'Access-Control-Allow-Headers',
            'Content-Type, Authorization, X-Requested-With'
        );
        $request = $this->getRequest();
        if ($request->getMethod() == 'OPTIONS') {
            return $this->output(null, 204);
        }
        return parent::onDispatch($e);
    }

    /**
     * Save request data to auth_hash table.
     *
     * @return \Laminas\Http\Response
     */
    public function handshakeAction()
    {
        $requestParams = json_decode($this->getRequest()->getContent(), true);

        $uuid = $this->getConfig()->Bazaar->uuid['moodle'];

        $csrf = $this->serviceLocator->get(\VuFind\Validator\CsrfInterface::class);
        if ($uuid == $requestParams['uuid']) {
            $hash = $csrf->getHash(true);
            $data = [
                'uuid' => $requestParams['uuid'],
                'return_url' => $requestParams['return_url'],
            ];
            $authHash = $this->serviceLocator
                ->get(\VuFind\Db\Table\PluginManager::class)
                ->get(\VuFind\Db\Table\AuthHash::class);
            $authHash->insert(
                ['hash' => $hash,
                'type' => 'bazaar',
                'data' => json_encode($data, JSON_UNESCAPED_UNICODE)
                ]
            );

            $baseUrl = $this->getServerUrl('home');
            $viewUrl = $baseUrl . 'Search/Bazaar?hash=';
            $response = [
                'view_url' => $viewUrl . $hash
            ];

            return $this->output($response, self::STATUS_OK);
        } else {
            return $this->output([], self::STATUS_ERROR, 401, 'Unauthorized');
        }
    }

    /**
     * Get API specification JSON fragment for services provided by the
     * controller
     *
     * @return string
     */
    public function getApiSpecFragment()
    {
        $spec = [];
        $spec['paths']['/bazaar']['get'] = [
            'summary' => 'Get view url for selection',
            'description' => 'Returns a view url for selection',
            'parameters' => [],
            'tags' => ['bazaar'],
            'requestBody' => [
                'required' => true,
                'content' => [
                    'application/json' => [
                        'schema' => [
                            'type' => 'object',
                            'properties' => [
                                'uuid' => [
                                    'type' => 'string',
                                    'description' => 'Predefined uuid '
                                    . 'that matches config uuid',
                                ],
                                'return_url' => [
                                    'type' => 'string',
                                    'description' => 'Return url for exiting '
                                    . 'selection and sending selected id',
                                ],
                            ],
                            'required' => [
                                'uuid',
                                'return_url',
                            ],
                        ],
                    ],
                ],
            ],
            'responses' => [
                '200' => [
                    'description' => 'View url',
                    'content' => [
                        'application/json' => [
                            'schema' => [
                                'properties' => [
                                    'view_url' => [
                                        'description' => 'View url for '
                                        . 'the selection view',
                                        'type' => 'string'
                                    ],
                                    'status' => [
                                        'description' => 'Status code',
                                        'type' => 'string',
                                        'enum' => ['OK']
                                    ],
                                ],
                            ],
                        ],
                        'required' => ['status']
                    ]
                ],
                'default' => [
                    'description' => 'Error',
                    'content' => [
                        'application/json' => [
                            'schema' => [
                                '$ref' => '#/components/schemas/Error'
                            ]
                        ]
                    ]
                ]
            ]
        ];

        return json_encode($spec);
    }
}
