<?php
/**
 * Selection API Controller
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

use Finna\Controller\L1RecordController;
use Laminas\Http\Response;
use Laminas\ServiceManager\ServiceLocatorInterface;
use Laminas\Stdlib\Parameters;
use Laminas\Config\Config;
use Laminas\Session\Container as SessionContainer;
use VuFind\Config\PluginManager;
use VuFindApi\Controller\ApiInterface;
use VuFindApi\Controller\ApiTrait;
use VuFindApi\Formatter\RecordFormatter;

/**
 * Selection API Controller
 *
 * Controls the Selection API functionality
 *
 * @category VuFind
 * @package  Service
 * @author   Aida Luuppala <aida.luuppala@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:controllers Wiki
 */
class SelectionApiController extends L1RecordController implements ApiInterface
{
    use ApiTrait;

    /**
     * Constructor
     *
     * @param ServiceLocatorInterface $sm Service manager
     * @param Config $config Configuration
     */
    public function __construct(ServiceLocatorInterface $sm, Config $config)
    {
        parent::__construct($sm, $config);
    }

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
        $headers->addHeaderLine('Access-Control-Allow-Methods', 'GET, POST, OPTIONS');
        $headers->addHeaderLine('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With');
        $request = $this->getRequest();
        if ($request->getMethod() == 'OPTIONS') {
            return $this->output(null, 204);
        }
        return parent::onDispatch($e);
    }

    /**
     * Save info to session.
     *
     * @return \Laminas\Http\Response
     */
    public function startAction()
    {
        $requestParams = json_decode($this->getRequest()->getContent(), true);

        $session = new \Laminas\Session\Container(
            \Finna\View\Helper\Root\TestViewHelper::SESSION_NAME,
            $this->serviceLocator->get(\Laminas\Session\SessionManager::class)
        );

        $session['source'] = $requestParams['source'];
        $session['returnLink'] = $requestParams['return_link'];

        return $this->output([],self::STATUS_OK);
    }

    /**
     * Send selected record.
     *
     * @return \Laminas\Http\Response
     */
    public function sendAction()
    {
        $requestParams = $this->getRequest()->getQuery()->toArray()
            + $this->getRequest()->getPost()->toArray();

        if (!isset($requestParams['id'])) {
            return $this->output([], self::STATUS_ERROR, 400, 'Missing id');
        }
        try {
            $driver = $this->loadRecord();
            $raw_data = $driver->getRawData();
            $fullrecord = $raw_data['fullrecord'];

            // ALLI-7524 TODO: Select sendable data and send it to moodle / javascript from view side

            $response = [
                'id' => $requestParams['id'],
                'fullrecord' => $fullrecord,
            ];

            return $this->output($response, self::STATUS_OK);
        } catch (RecordMissingException $e) {
            return $this->output([], self::STATUS_ERROR, 404, 'Record not found');
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
        // ALLI-7524 TODO: Update api spec to match selection api
        $spec = [];
        $spec['paths']['/selection']['get'] = [
            'summary' => 'Get something',
            'description' => 'Lists the possible login targets.',
            'parameters' => [],
            'tags' => ['select'],
            'responses' => [
                '200' => [
                    'description' => 'List of targets',
                    'content' => [
                        'application/json' => [
                            'schema' => [
                                'properties' => [
                                    'targets' => [
                                        'description' => 'Login targets',
                                        'type' => 'array',
                                        'items' => [
                                            'type' => 'object',
                                            'properties' => [
                                                'id' => [
                                                    'description'
                                                        => 'Target identifier',
                                                    'type' => 'string'
                                                ],
                                                'name' => [
                                                    'description'
                                                        => 'Target name',
                                                    'type' => 'string'
                                                ],
                                            ],
                                        ],
                                    ],
                                    'status' => [
                                        'description' => 'Status code',
                                        'type' => 'string',
                                        'enum' => ['OK']
                                    ],
                                ],
                            ],
                        ],
                        'required' => ['resultCount', 'status']
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
