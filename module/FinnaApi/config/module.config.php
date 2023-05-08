<?php

namespace FinnaApi\Module\Configuration;

$config = [
    'controllers' => [
        'factories' => [
            'FinnaApi\Controller\AdminApiController' => 'VuFindApi\Controller\AdminApiControllerFactory',
            'FinnaApi\Controller\AuthApiController' => 'FinnaApi\Controller\AuthApiControllerFactory',
            'FinnaApi\Controller\BazaarApiController' => 'FinnaApi\Controller\BazaarApiControllerFactory',
            'FinnaApi\Controller\ListApiController' => 'FinnaApi\Controller\ListApiControllerFactory',
            'FinnaApi\Controller\SearchApiController' => 'VuFindApi\Controller\SearchApiControllerFactory',
            'VuFindApi\Controller\ApiController' => 'FinnaApi\Controller\ApiControllerFactory',
        ],
        'aliases' => [
            'AdminApi' => 'FinnaApi\Controller\AdminApiController',
            'AuthApi' => 'FinnaApi\Controller\AuthApiController',
            'BazaarApi' => 'FinnaApi\Controller\BazaarApiController',
            'ListApi' => 'FinnaApi\Controller\ListApiController',
            'VuFindApi\Controller\SearchApiController' => 'FinnaApi\Controller\SearchApiController',

            'adminapi' => 'AdminApi',
            'authapi' => 'AuthApi',
            'bazaarapi' => 'BazaarApi',
            'listapi' => 'ListApi',
        ],
    ],
    'service_manager' => [
        'factories' => [
            'FinnaApi\Formatter\RecordFormatter' => 'FinnaApi\Formatter\RecordFormatterFactory',
        ],
        'aliases' => [
            'VuFindApi\Formatter\RecordFormatter' => 'FinnaApi\Formatter\RecordFormatter',
        ],
    ],
    'vufind_api' => [
        'register_controllers' => [
            \FinnaApi\Controller\AuthApiController::class,
            \FinnaApi\Controller\BazaarApiController::class,
            \FinnaApi\Controller\ListApiController::class,
        ],
    ],
    'router' => [
        'routes' => [
            'adminApi' => [
                'type' => 'Laminas\Router\Http\Segment',
                'verb' => 'get,post,options',
                'options' => [
                    'route'    => '/adminapi[/v1][/]',
                    'defaults' => [
                        'controller' => 'AdminApi',
                        'action'     => 'Index',
                    ],
                ],
            ],
            'apiHomeBareV1' => [
                'type' => 'Laminas\Router\Http\Segment',
                'verb' => 'get,post,options',
                'options' => [
                    'route'    => '/v1[/]',
                    'defaults' => [
                        'controller' => 'Api',
                        'action'     => 'Index',
                    ],
                ],
            ],
            'authApiV1' => [
                'type' => 'Laminas\Router\Http\Segment',
                'verb' => 'get,post,options',
                'options' => [
                    'route'    => '/api/v1/auth/[:action]',
                    'defaults' => [
                        'controller' => 'AuthApi',
                    ],
                ],
            ],
            'bazaarApiV1' => [
                'type' => 'Laminas\Router\Http\Literal',
                'verb' => 'get,post,options',
                'options' => [
                    'route'    => '/api/v1/bazaar/browse',
                    'defaults' => [
                        'controller' => 'BazaarApi',
                        'action'     => 'browse',
                    ],
                ],
            ],
            'listApiV1' => [
                'type' => 'Laminas\Router\Http\Literal',
                'verb' => 'get,post,options',
                'options' => [
                    'route'    => '/api/v1/list',
                    'defaults' => [
                        'controller' => 'ListApi',
                        'action'     => 'list',
                    ],
                ],
            ],
            'searchApiBareV1' => [
                'type' => 'Laminas\Router\Http\Literal',
                'verb' => 'get,post,options',
                'options' => [
                    'route'    => '/v1/search',
                    'defaults' => [
                        'controller' => 'SearchApi',
                        'action'     => 'search',
                    ],
                ],
            ],
            'recordApiBareV1' => [
                'type' => 'Laminas\Router\Http\Literal',
                'verb' => 'get,post,options',
                'options' => [
                    'route'    => '/v1/record',
                    'defaults' => [
                        'controller' => 'SearchApi',
                        'action'     => 'record',
                    ],
                ],
            ],
        ],
    ],
];

return $config;
