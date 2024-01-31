<?php

/**
 * Reservation List Controller
 *
 * PHP version 8
 *
 * Copyright (C) The National Library of Finland 2015-2019.
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
 * @package  Controller
 * @author   Juha Luoma <juha.luoma@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org   Main Site
 */

namespace Finna\Controller;

use Exception;
use Finna\ReservationList\ReservationListService;
use VuFind\Controller\AbstractBase;

/**
 * Reservation List Controller
 *
 * @category VuFind
 * @package  Controller
 * @author   Juha Luoma <juha.luoma@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org   Main Site
 */
class ReservationListController extends AbstractBase
{
  protected function getParam(string $param): mixed
  {
    return $this->params()->fromRoute(
      $param, 
      $this->params()->fromPost(
        $param,
        $this->params()->fromQuery($param)
      )
    );
  }
  /**
   * Display save to reservation list form.
   *
   * @return \Laminas\View\Model\ViewModel
   * @throws \Exception
   */
  public function addItemAction()
  {
    $user = $this->getAuthManager();
    if (!$user) {
      // Route to log in
    }
    $driver = $this->getRecordLoader()->load(
      $this->getParam('id'),
      $this->getParam('source') ?: DEFAULT_SEARCH_BACKEND,
      false
    );
    // Now we should try to find all the lists for user.. Lets check if this works somehow or something
    $reservationListService = $this->serviceLocator->get(ReservationListService::class);
    $view = $this->createViewModel(
      compact(
        'driver'
      )
    );
    if ($this->formWasSubmitted('submit')) {
      $params = $this->params()->fromPost();
      // Seems like someone wants to save stuff into a list.
      // Lets process it like a champ. Not the mushroom champ.
      $reservationListService->addRecordToList(
        $this->getUser(),
        $driver->getUniqueID(),
        $params['list'],
        $params['notes'],
        $driver->getSourceIdentifier()
      );
      // After this we can display the you did it, lets continue screen
      $view->setTemplate('reservationlist/add-success');
      return $view;
    }
    $view->lists = $reservationListService->getListsForDatasource($this->getUser(), $driver->getDatasource());
    $view->setTemplate('reservationlist/select-list');
    return $view;
  }

  public function homeAction(): \Laminas\View\Model\ViewModel
  {
    // Fail if lists are disabled:
        if (!$this->listsEnabled()) {
        throw new ForbiddenException('Lists disabled');
    }

    // Check for "delete item" request; parameter may be in GET or POST depending
    // on calling context.
    $deleteId = $this->params()->fromPost(
        'delete',
        $this->params()->fromQuery('delete')
    );
    if ($deleteId) {
        $deleteSource = $this->params()->fromPost(
            'source',
            $this->params()->fromQuery('source', DEFAULT_SEARCH_BACKEND)
        );
        // If the user already confirmed the operation, perform the delete now;
        // otherwise prompt for confirmation:
        $confirm = $this->params()->fromPost(
            'confirm',
            $this->params()->fromQuery('confirm')
        );
        if ($confirm) {
            $success = $this->performDeleteFavorite($deleteId, $deleteSource);
            if ($success !== true) {
                return $success;
            }
        } else {
            return $this->confirmDeleteFavorite($deleteId, $deleteSource);
        }
    }
    $reservationListService = $this->serviceLocator->get(ReservationListService::class);
    // We want to merge together GET, POST and route parameters to
    // initialize our search object:
    $request = $this->getRequest()->getQuery()->toArray()
        + $this->getRequest()->getPost()->toArray()
        + ['id' => $this->params()->fromRoute('id')];
    
    if (!isset($request['id'])) {
        $lists = $reservationListService->getListsForUser($this->getUser());
        $request['id'] = reset($lists)['id'] ?? null;
    }

    if (isset($request['id'])) {
        // If we got this far, we just need to display the favorites:
        try {
            $runner = $this->serviceLocator->get(\VuFind\Search\SearchRunner::class);

            // We want to merge together GET, POST and route parameters to
            // initialize our search object:
            $request = $this->getRequest()->getQuery()->toArray()
                + $this->getRequest()->getPost()->toArray()
                + ['id' => $this->params()->fromRoute('id')];

            // Set up listener for recommendations:
            $rManager = $this->serviceLocator
                ->get(\VuFind\Recommend\PluginManager::class);
            $setupCallback = function ($runner, $params, $searchId) use ($rManager) {
                $listener = new \VuFind\Search\RecommendListener($rManager, $searchId);
                $listener->setConfig(
                    $params->getOptions()->getRecommendationSettings()
                );
                $listener->attach($runner->getEventManager()->getSharedManager());
            };

            $results = $runner->run($request, 'ReservationList', $setupCallback);
            return $this->createViewModel(
                [
                    'params' => $results->getParams(), 'results' => $results
                ]
            );
        } catch (ListPermissionException $e) {
            if (!$this->getUser()) {
                return $this->forceLogin();
            }
            throw $e;
        }
    }

    $view = $this->createViewModel();
    $view->lists = $reservationListService->getListsForUser($this->getUser());
    return $view;
  }

  /**
   * Display save to reservation list form.
   *
   * @return \Laminas\View\Model\ViewModel
   * @throws \Exception
   */
  public function addListAction(): \Laminas\View\Model\ViewModel
  {
    $action = $this->params()->fromPost(
      'action',
      $this->params()->fromQuery('action')
    );
    $source = $this->params()->fromPost(
        'source',
        $this->params()->fromQuery(
            'source',
            DEFAULT_SEARCH_BACKEND
        )
    );
    $id = $this->params()->fromPost(
        'recordId',
        $this->params()->fromQuery(
            'recordId',
        )
    );
    $driver = $this->getRecordLoader()->load($id, $source, true);
    $view = $this->createViewModel(
        [
            'driver' => $driver,
            'action' => $action,
        ]
    );

    if ($this->formWasSubmitted('submit')) {
      // Check which form was submitted
      $params = $this->params()->fromPost(null, []);
      $building = $driver->getBuildings()[0] ?? '';
      $datasource = $driver->getDataSource();
      switch ($params['action'] ?? '') {
          case 'add':
              break;
          case 'edit':
              $reservationListService = $this->serviceLocator->get(ReservationListService::class);
              // Get the service which handles the lists
              $reservationListService->addListForUser(
                  $this->getUser(),
                  $params['desc'],
                  $params['title'],
                  $params['datasource'],
              );
              return $this->redirect()->toRoute('record-reservationlist', ['id' => $driver->getUniqueID()]);
              break;
          default:
              break;
      }
    }
    switch ($action) {
        case 'add':
            // Add record to an existing list
            $view->setTemplate('record/reservation-list.phtml');
            break;
        case 'newList':
            // Display newList page
            break;
        case 'delete':
            // Try to delete and delete if plausible
            break;
        case 'edit':
            $view->setTemplate('reservationlist/add-list.phtml');
            // Display edit list thingie page
            break;
        default:
            break;
    }
    return $view;
  }
}
