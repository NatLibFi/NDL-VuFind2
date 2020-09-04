<?php

/**
 * Restricted Solr (R2) Search authenticationlistener.
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
 * @link     https://vufind.org Main Site
 */
namespace Finna\Search\R2;

use Finna\Service\R2Service;
use Finna\Service\RemsService;
use FinnaSearch\Backend\R2\Connector;

use Laminas\EventManager\EventInterface;
use Laminas\EventManager\SharedEventManagerInterface;

use VuFind\Auth\Manager;
use VuFindSearch\Backend\BackendInterface;

/**
 * Restricted Solr (R2) Search authentication listener.
 *
 * @category VuFind
 * @package  Search
 * @author   Samuli Sillanpää <samuli.sillanpaa@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org Main Site
 */
class AuthenticationListener
{
    /**
     * Backend.
     *
     * @var BackendInterface
     */
    protected $backend;

    /**
     * Authentication manager
     *
     * @var Manager
     */
    protected $authManager;

    /**
     * R2 service
     *
     * @var R2Service;
     */
    protected $R2Service;

    /**
     * Connector
     *
     * @var FinnaSearch\Backend\R2\Connector
     */
    protected $connector;

    /**
     * Rems Service
     *
     * @var RemsService
     */
    protected $rems;

    /**
     * Constructor.
     *
     * @param BackendInterface $backend     Search backend
     * @param Manager          $authManager Authentication manager
     * @param R2Service        $R2Service   R2 service
     * @param Connector        $connector   Backend connector
     * @param RemsService      $rems        REMS service
     *
     * @return void
     */
    public function __construct(
        BackendInterface $backend,
        Manager $authManager,
        R2Service $R2Service,
        Connector $connector,
        RemsService $rems
    ) {
        $this->backend = $backend;
        $this->authManager = $authManager;
        $this->R2Service = $R2Service;
        $this->connector = $connector;
        $this->rems = $rems;
    }

    /**
     * Attach listener to shared event manager.
     *
     * @param SharedEventManagerInterface $manager Shared event manager
     *
     * @return void
     */
    public function attach(
        SharedEventManagerInterface $manager
    ) {
        $manager->attach('VuFind\Search', 'pre', [$this, 'onSearchPre']);
    }

    /**
     * Add username as a search parameter if the user is authorized.
     *
     * @param EventInterface $event Event
     *
     * @return EventInterface
     */
    public function onSearchPre(EventInterface $event)
    {
        $backend = $event->getTarget();
        if ($backend === $this->backend) {
            $params = $event->getParam('params');
            $context = $event->getParam('context');
            $this->connector->setUsername(null);
            // Attempt to retrieve restricted metadata from the backend
            // when the following holds:
            // 1. The user is authorized
            // (properly authenticated & registered to REMS)
            // 2. If the search context is retrieve or retrieveBatch,
            // and restricted metadata was requested.
            if (!in_array($context, ['retrieve', 'retrieveBatch'])
                || in_array(true, $params->get('R2Restricted') ?? [])
            ) {
                // Verify that the user is authorized.
                if ($this->R2Service->isAuthenticated()
                    && $this->rems->isUserRegisteredDuringSession()
                ) {
                    // Pass the username to connector in order to
                    // request restricted metadata.
                    $userId = $this->rems->prepareUserId(
                        $this->authManager->isLoggedIn()->username
                    );
                    $this->connector->setUsername(urlencode($userId));
                }
            }
        }
        return $event;
    }
}
