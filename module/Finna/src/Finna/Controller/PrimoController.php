<?php

/**
 * Primo Central Controller.
 *
 * PHP version 8
 *
 * Copyright (C) The National Library of Finland 2015.
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
 * @package  Controller
 * @author   Samuli Sillanpää <samuli.sillanpaa@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:controllers Wiki
 */

namespace Finna\Controller;

use Laminas\Mvc\MvcEvent;

use function is_object;

/**
 * Primo Central Controller.
 *
 * @category VuFind
 * @package  Controller
 * @author   Samuli Sillanpää <samuli.sillanpaa@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:controllers Wiki
 */
class PrimoController extends \VuFind\Controller\PrimoController
{
    use FinnaSearchControllerTrait;

    /**
     * Search class family to use.
     *
     * @var string
     */
    protected $searchClassId = 'Primo';

    /**
     * Use preDispatch event to block access when appropriate.
     *
     * @param MvcEvent $e Event object
     *
     * @return void
     */
    public function validateAccessPermission(MvcEvent $e)
    {
        // If there is an access permission set for this controller, pass it
        // through the permission helper, and if the helper returns a custom
        // response, use that instead of the normal behavior.
        if ($this->accessPermission) {
            $response = $this->permission()
                ->check($this->accessPermission, $this->accessDeniedBehavior);
            if (is_object($response)) {
                $e->setResponse($response);
                $e->stopPropagation();
            }
        }
    }

    /**
     * Home action.
     *
     * @return mixed
     */
    public function homeAction()
    {
        $this->layout()->searchClassId = $this->searchClassId;
        return parent::homeAction();
    }

    /**
     * Handle onDispatch event.
     *
     * @param \Laminas\Mvc\MvcEvent $e Event
     *
     * @return mixed
     */
    public function onDispatch(\Laminas\Mvc\MvcEvent $e)
    {
        $primoHelper = $this->getViewRenderer()->plugin('primo');
        if (!$primoHelper->isAvailable()) {
            throw new \Exception('Primo is disabled');
        }

        return parent::onDispatch($e);
    }

    /**
     * Search action -- call standard results action.
     *
     * @return mixed
     */
    public function searchAction()
    {
        if ($this->getRequest()->getQuery()->get('combined')) {
            $this->saveToHistory = false;
        }
        $this->initCombinedViewFilters();
        $view = parent::resultsAction();
        $this->initSavedTabs();

        return $view;
    }
}
