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
use Laminas\ServiceManager\ServiceLocatorInterface;
use Laminas\Session\Container;
use VuFind\Controller\AbstractBase;
use VuFind\Mailer\Mailer;

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

  /**
   * Reservation list service
   *
   * @var ReservationListService
   */
  protected $reservationListService;

  /**
   * Constructor
   *
   * @param ServiceLocatorInterface      $sm                     Service locator
   * @param Container                    $container              Session container
   * @param \VuFind\Config\PluginManager $configLoader           Configuration loader
   * @param \VuFind\Export               $export                 Export support class
   * @param ReservationListService       $reservationListService Reservation list service
   */
  public function __construct(
    ServiceLocatorInterface $sm,
    Container $container,
    \VuFind\Config\PluginManager $configLoader,
    \VuFind\Export $export,
    ReservationListService $reservationListService
  ) {
      parent::__construct($sm, $container, $configLoader, $export);
      $this->reservationListService = $reservationListService;
  }

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
   * Add item to list action.
   *
   * @return \Laminas\View\Model\ViewModel
   * @throws \Exception
   */
  public function addItemAction()
  {
    $user = $this->getUser();
    if (!$user) {
        return $this->forceLogin();
    }

    // Now we should try to find all the lists for user.. Lets check if this works somehow or something
    $reservationListService = $this->serviceLocator->get(ReservationListService::class);
    $view = $this->createViewModel();
    $recordId = $this->getParam('recordId');
    $source = $this->getParam('source');
    $newList = $this->getParam('newList');
    if ($newList && !$this->formWasSubmitted('submit')) {
      $view->setTemplate('reservationlist/add-list');
      $view->source = $source;
      $view->recordId = $recordId;
      return $view;
    }
    $driver = $this->getRecordLoader()->load(
      $recordId,
      $source ?: DEFAULT_SEARCH_BACKEND,
      false
    );
    // At this point we should check if the record is proper

    $view->driver = $driver;
    if ($this->formWasSubmitted('submit')) {
      $state = $this->getParam('state');
      if ('saveList' === $state) {
        $building = $driver->getBuildings()[0] ?? '';
        $datasource = $driver->getDataSource();
        $this->reservationListService->addListForUser(
          $this->getUser(),
          $this->getParam('desc') ?? '',
          $this->getParam('title') ?? '',
          $datasource,
          $building,
        );
        $view->lists = $reservationListService->getListsForDatasource($this->getUser(), $driver->getDatasource());
        $view->setTemplate('reservationlist/select-list');
      } elseif ('saveItem' === $state) {
        // Seems like someone wants to save stuff into a list.
        // Lets process it like a champ. Not the mushroom champ.
        $this->reservationListService->addRecordToList(
          $this->getUser(),
          $driver->getUniqueID(),
          $this->getParam('list'),
          $this->getParam('desc') ?? '',
          $driver->getSourceIdentifier()
        );
        // After this we can display the you did it, lets continue screen
        $view->setTemplate('reservationlist/add-success');
      }
      return $view;
    }

    $view->lists = $reservationListService->getListsForDatasource($this->getUser(), $driver->getDatasource());
    $view->setTemplate('reservationlist/select-list');
    return $view;
  }

  /**
   * Home 
   */
  public function homeAction(): \Laminas\View\Model\ViewModel
  {
    $user = $this->getUser();
    if (!$user) {
        return $this->forceLogin();
    }
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
    // We want to merge together GET, POST and route parameters to
    // initialize our search object:
    $request = $this->getRequest()->getQuery()->toArray()
        + $this->getRequest()->getPost()->toArray()
        + ['id' => $this->params()->fromRoute('id')];
    
    // Get first list for user to show if none is displayed
    if (!isset($request['id'])) {
        $lists = $this->reservationListService->getListsForUser($this->getUser());
        $firstList = reset($lists);
        $request['id'] = $firstList['id'] ?? null;
    }

    if (isset($request['id'])) {
      $results = $this->getListAsResults($request);
      $currentList = $this->reservationListService->getListForUser($user, $request['id']);
      // If we got this far, we just need to display the favorites:
      try {
          return $this->createViewModel(
              [
                  'params' => $results->getParams(),
                  'results' => $results,
                  'title' => $currentList->title,
                  'description' => $currentList->description,
                  'datasource' => $currentList->datasource,
                  'created' => $currentList->created,
                  'ordered' => $currentList->ordered,
                  'building' => $currentList->building,
                  'listId' => $request['id'],
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
    return $view;
  }

  /**
   * Display save to reservation list form.
   *
   * @return \Laminas\View\Model\ViewModel
   * @throws \Exception
   */
  public function orderAction(): \Laminas\View\Model\ViewModel
  {
    // We want to merge together GET, POST and route parameters to
    // initialize our search object:
    $request = $this->getRequestAsArray();
    $currentDate = date('Y-m-d');
    $earliestPickup = date('Y-m-d', strtotime('+ 2 days'));
    $listId = $request['list-id'] ?? $request['id'] ?? '';
    $list = $this->reservationListService->getListForUser($this->getUser(), $listId);
    $results = $this->getListAsResults($request);
    // Building or datasource, depends?
    $listConfg = $this->getConfig('ReservationList')[$list['datasource']];
    if ($this->formWasSubmitted('submit')) {
      // Gather data here, and be ready to send an email.
      // Or let the reservationlistservice to handle the sending.
      $dateToPickUp = $request['order-pickup-date'] ?? false;
      $contactInfo = $request['order-email'] ?? $request['order-phone'] ?? false;
      // If any of the following information is missing, return the list and add a small warning
      if (in_array(false, [$dateToPickUp, $contactInfo, $listId])) {
        // Do an error check here
      }
      $user = $this->getUser();
      // Start order process
      $config = $this->getConfig();
      $message = $this->getViewRenderer()->render(
        'Email/reservation-list.phtml',
        compact(
          'dateToPickUp',
          'contactInfo',
          'results',
          'user'
        ),
      );
      $to = 'testemail@test.test';
      $config = $this->getConfig();
      $from = $this->getConfig()->Site->email;
      // If everything goes right, save the ordered timestamp, otherwise return with message about what went wrong
      try {
        $this->serviceLocator->get(Mailer::class)->send(
          $listConfg['email'],
          $from,
          $this->translate('tilauslista tilaus'),
          $message
        );
        $this->reservationListService->setOrdered($user, $listId);
      } catch (\VuFind\Exception\Mail $e) {
        $this->flashMessenger()->addMessage($e->getDisplayMessage());
      }
    }

    $organisationInfo = [
      'name' => $listConfg['name'] ?? '-',
      'address' => $listConfg['address'] ?? '-',
      'postal' => $listConfg['postal'] ?? '-',
      'city' => $listConfg['city'] ?? '-',
    ];

    $view = $this->createViewModel(
      compact(
        'results',
        'earliestPickup',
        'currentDate',
        'organisationInfo',
        'listId'
      ),
    );
    return $view;
  }

  /**
   * Delete a list
   */
  public function deleteAction()
  {
    // We want to merge together GET, POST and route parameters to
    // initialize our search object:
    $request = $this->getRequestAsArray();
    if ($this->formWasSubmitted('submit')) {
      $result = $this->reservationListService->deleteList($this->getUser(), $request['id']);
      if ($result) {
        $this->flashMessenger()->addMessage('List removed successfully');
        return $this->redirect()->toRoute('reservationlist-home');
      }
    }

    $view = $this->createViewModel(
      ['id' => $request['id']]
    );
    return $view;
  }

  protected function getRequestAsArray(): array
  {
    $request = $this->getRequest()->getQuery()->toArray()
      + $this->getRequest()->getPost()->toArray();
    
    if (!null !== $this->params()->fromRoute('id')) {
      $request += ['id' => $this->params()->fromRoute('id')];
    }
    return $request;
  }

  protected function getListAsResults($request)
  {
    $runner = $this->serviceLocator->get(\VuFind\Search\SearchRunner::class);
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

    return $runner->run($request, 'ReservationList', $setupCallback);
  }
}
