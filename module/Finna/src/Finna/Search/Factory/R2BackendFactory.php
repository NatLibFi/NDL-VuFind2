<?php

/**
 * Abstract factory for restricted Solr (R2) backends.
 *
 * PHP version 7
 *
 * Copyright (C) The National Library of Finland 2020.
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
 * @package  Search
 * @author   Samuli Sillanpää <samuli.sillanpaa@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org   Main Site
 */
namespace Finna\Search\Factory;

use Finna\Search\R2\AuthenticationListener;
use Interop\Container\ContainerInterface;
use VuFindSearch\Backend\Solr\Backend;
use VuFindSearch\Backend\Solr\Connector;

use VuFindSearch\Backend\Solr\Response\Json\RecordCollectionFactory;

/**
 * Abstract factory for R2 backends.
 *
 * @category VuFind
 * @package  Search
 * @author   Samuli Sillanpää <samuli.sillanpaa@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org   Main Site
 */
class R2BackendFactory extends SolrDefaultBackendFactory
{
    /**
     * R2 configuration.
     *
     * @var \Laminas\Config\Config
     */
    protected $R2Config;

    /**
     * R2 service
     *
     * @var \Finna\Service\R2Service
     */
    protected $R2Service;

    /**
     * Rems Service
     *
     * @var \Finna\Service\RemsService
     */
    protected $rems;

    /**
     * Solr connector class
     *
     * @var string
     */
    protected $connectorClass = \FinnaSearch\Backend\R2\Connector::class;

    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();
        $this->mainConfig = $this->facetConfig = $this->searchConfig = 'R2';
    }

    /**
     * Create service
     *
     * @param ContainerInterface $sm      Service manager
     * @param string             $name    Requested service name (unused)
     * @param array              $options Extra options (unused)
     *
     * @return Backend
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function __invoke(ContainerInterface $sm, $name, array $options = null)
    {
        $this->R2Config = $sm->get('VuFind\Config\PluginManager')->get('R2');
        $this->R2Service = $sm->get(\Finna\Service\R2Service::class);
        $this->solrCore = $this->R2Config->Index->default_core;
        $this->R2Service
            = $sm->get(\Finna\Service\R2Service::class);
        $this->rems = $sm->get(\Finna\Service\RemsService::class);

        return parent::__invoke($sm, $name, $options);
    }

    /**
     * Create the SOLR connector.
     *
     * @return Connector
     */
    protected function createConnector()
    {
        $connector = parent::createConnector();
        $credentials = $this->R2Service->getCredentials();
        $connector->setApiAuthentication(
            $credentials['apiUser'], $credentials['apiKey']
        );
        $connector->setRems($this->rems);
        $connector->setHttpOptions($this->R2Config->Http->toArray());

        return $connector;
    }

    /**
     * Create the SOLR backend.
     *
     * @param Connector $connector Connector
     *
     * @return Backend
     */
    protected function createBackend(Connector $connector)
    {
        $backend = parent::createBackend($connector);
        $manager = $this->serviceLocator
            ->get(\VuFind\RecordDriver\PluginManager::class);

        $callback = function ($data) use ($manager) {
            $driver = $manager->get('R2Ead3');
            $driver->setRawData($data);
            return $driver;
        };

        $factory = new RecordCollectionFactory($callback);
        $backend->setRecordCollectionFactory($factory);
        return $backend;
    }

    /**
     * Create listeners.
     *
     * @param Backend $backend Backend
     *
     * @return void
     */
    protected function createListeners(Backend $backend)
    {
        parent::createListeners($backend);

        $events = $this->serviceLocator->get('SharedEventManager');
        $authListener = new AuthenticationListener(
            $backend,
            $this->R2Service,
            $backend->getConnector(),
            $this->rems
        );
        $authListener->attach($events);
    }

    /**
     * Get the Solr URL.
     *
     * @param string $config name of configuration file (null for default)
     *
     * @return string|array
     */
    protected function getSolrUrl($config = null)
    {
        $url = $this->R2Config->Index->url;
        return "$url/" . $this->solrCore;
    }
}
