<?php

/**
 * Reservation List Controller
 *
 * PHP version 8
 *
 * Copyright (C) The National Library of Finland 2024.
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
 * @author   Samuli Sillanpää <samuli.sillanpaa@helsinki.fi>
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @author   Konsta Raunio <konsta.raunio@helsinki.fi>
 * @author   Kalle Pyykkönen <kalle.pyykkonen@helsinki.fi>
 * @author   Juha Luoma <juha.luoma@helsinki.fi>
 * @author   Tuure Ilmarinen <tuure.ilmarinen@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org   Main Site
 */

namespace Finna\Controller;

use Finna\Form\Form;
use Finna\ReservationList\ReservationListService;
use Finna\View\Helper\Root\ReservationList;
use Laminas\ServiceManager\ServiceLocatorInterface;
use Laminas\Stdlib\Parameters;
use VuFind\Controller\AbstractBase;
use VuFind\Exception\ListPermission as ListPermissionException;
use VuFind\Exception\LoginRequired as LoginRequiredException;
use VuFind\Exception\RecordMissing as RecordMissingException;

/**
 * Reservation List Controller
 *
 * @category VuFind
 * @package  Controller
 * @author   Samuli Sillanpää <samuli.sillanpaa@helsinki.fi>
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @author   Konsta Raunio <konsta.raunio@helsinki.fi>
 * @author   Kalle Pyykkönen <kalle.pyykkonen@helsinki.fi>
 * @author   Juha Luoma <juha.luoma@helsinki.fi>
 * @author   Tuure Ilmarinen <tuure.ilmarinen@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org   Main Site
 */
class ReservationListController extends AbstractBase
{
    /**
     * Constructor
     *
     * @param ServiceLocatorInterface $sm                     Service locator
     * @param ReservationListService  $reservationListService Reservation list service
     * @param ReservationList         $reservationListHelper  Reservation list helper
     */
    public function __construct(
        ServiceLocatorInterface $sm,
        protected ReservationListService $reservationListService,
        protected ReservationList $reservationListHelper
    ) {
        parent::__construct($sm);
    }

    /**
     * Retrieves the value of the specified parameter.
     *
     * @param string $param   The name of the parameter to retrieve.
     * @param mixed  $default Default value to return if not found
     *
     * @return mixed The value of the specified parameter.
     */
    protected function getParam(string $param, mixed $default = null): mixed
    {
        return $this->params()->fromRoute(
            $param,
            $this->params()->fromPost(
                $param,
                $this->params()->fromQuery(
                    $param,
                    $default
                )
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
        $view = $this->createViewModel();
        $view->institution = $institution = $this->getParam('institution');
        $view->listIdentifier = $listIdentifier = $this->getParam('listIdentifier');

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
        $configuration = ($this->reservationListHelper)($user)->getListConfiguration($institution, $listIdentifier);
        /**
         * Check if the driver is really compatible with given list
         */
        if (!$configuration) {
            throw new \VuFind\Exception\Forbidden('Record is not allowed in the list');
        }
        $view->driver = $driver;
        if ($this->formWasSubmitted('submit')) {
            $state = $this->getParam('state');
            $title = $this->getParam('title');
            $params = $this->params()->fromPost();
            $params['datasource'] = $driver->getDataSource();
            $params['building'] = $driver->getBuildings()[0] ?? '';
            if ('saveList' === $state) {
                if (!$title) {
                    $this->flashMessenger()->addErrorMessage('inventory_list_missing_title');
                    $view->setTemplate('reservationlist/add-list');
                    $view->source = $source;
                    $view->recordId = $recordId;
                    return $view;
                }
                $list = $this->reservationListService->createListForUser(
                    $this->getUser()
                );
                $this->reservationListService->updateListFromRequest(
                    $list['list_entity'],
                    $list['details_entity'],
                    $user,
                    new Parameters($params)
                );
            } elseif ('saveItem' === $state) {
                $this->reservationListService->saveRecordToResourceList(
                    $params,
                    $this->getUser(),
                    $driver,
                );
                return $this->inLightbox()  // different behavior for lightbox context
                  ? $this->getRefreshResponse()
                  : $this->redirect()->toRoute('home');
            }
        }
        $view->lists = $this->reservationListService->getListsNotContainingRecord(
            $this->getUser(),
            $driver->getUniqueID(),
            $source,
            $listIdentifier,
            $institution
        );

        $view->setTemplate('reservationlist/select-list');

        return $view;
    }

    /**
     * List action for the ReservationListController.
     *
     * @return \Laminas\View\Model\ViewModel|\Laminas\Http\Response
     */
    public function listAction(): \Laminas\View\Model\ViewModel|\Laminas\Http\Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->forceLogin();
        }
        $request = $this->getRequestAsArray();
        try {
            $list = $this->reservationListService->getListAndSettingsByListId($request['id'], $user);
        } catch (RecordMissingException $e) {
            return $this->redirect()->toRoute('reservationlist-home');
        }
        $results = $this->getListAsResults($request);
        $viewParams = [
            'list' => $list['list_entity'],
            'details' => $list['details_entity'],
            'results' => $results,
            'params' => $results->getParams(),
            'enabled' => true,
        ];
        try {
            return $this->createViewModel($viewParams);
        } catch (ListPermissionException $e) {
            if (!$this->getUser()) {
                return $this->forceLogin();
            }
            throw $e;
        }
    }

    /**
     * Handles rendering and submit of dynamic forms.
     * Form configurations are specified in FeedbackForms.yaml.
     *
     * @return mixed
     */
    public function orderAction()
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->forceLogin();
        }
        $listId = $this->getParam('id');
        // Check that list is not ordered or deleted
        $list = $this->reservationListService->getListAndSettingsByListId($listId, $user);
        $details = $list['details_entity'];
        if ($details->getOrdered()) {
            throw new \VuFind\Exception\Forbidden('List not found or ordered');
        }
        // Get all resources in the list as recordsIds
        $sourceRecordIds = array_map(
            fn ($resource) => $resource->getSource() . '|' . $resource->getRecordId(),
            $this->reservationListService->getResourcesForList($list['list_entity'], $user)
        );

        $configuration = $this->reservationListHelper->getListConfiguration(
            $details->getInstitution(),
            $details->getListConfigIdentifier()
        );
        $formId = $configuration['FormIdentifier'] ?? Form::RESERVATION_LIST_REQUEST;

        // Call form handler here instead of feedback-formaction
        return $this->redirect()->toRoute(
            'feedback-form',
            ['id' => $formId],
            ['query' => [
                'layout' => $this->getRequest()->getQuery('layout', false),
                'record_ids' => $sourceRecordIds,
                'rl_list_id' => $listId,
                'rl_institution' => $details->getInstitution(),
                'rl_list_identifier' => $details->getListConfigIdentifier(),
            ]]
        );
    }

    /**
     * Deletes a list.
     *
     * @return Response The response object.
     */
    public function deleteAction()
    {
        $listID = $this->getParam('id');
        if ($this->getParam('confirm')) {
            try {
                $user = $this->getUser();
                $list = $this->reservationListService->getAndRememberListObject($listID, $user);
                $this->reservationListService->destroyList($list, $user);
            } catch (LoginRequiredException | ListPermissionException $e) {
                $user = $this->getUser();
                if ($user == false) {
                    return $this->forceLogin();
                }
                // Logged in? Then we have to rethrow the exception!
                throw $e;
            }
            // Redirect to MyResearch home
            return $this->inLightbox()  // different behavior for lightbox context
                ? $this->getRefreshResponse()
                : $this->redirect()->toRoute('reservationlist-home');
        }
        return $this->confirmDelete(
            $listID,
            $this->url()->fromRoute('reservationlist-delete'),
            $this->url()->fromRoute('reservationlist-home'),
            'ReservationList::confirm_delete_list',
            [],
            ['id' => $listID]
        );
    }

    /**
     * Delete group of records from a list.
     *
     * @return mixed
     */
    public function deleteBulkAction()
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->forceLogin();
        }

        $listID = $this->getParam('listID', false);
        $ids = $this->getParam('ids');
        if (false === $listID) {
            throw new \Exception('List ID not defined in deleteBulkAction');
        }
        if ($this->getParam('confirm')) {
            try {
                $this->reservationListService->deleteResourcesFromList($ids, $listID, $user);
            } catch (LoginRequiredException | ListPermissionException $e) {
                $user = $this->getUser();
                if ($user == false) {
                    return $this->forceLogin();
                }
                // Logged in? Then we have to rethrow the exception!
                throw $e;
            }
            // Redirect to MyResearch home
            return $this->inLightbox()  // different behavior for lightbox context
                ? $this->getRefreshResponse()
                : $this->redirect()->toRoute('reservationlist-list', ['id' => $listID]);
        }
        return $this->confirmDelete(
            $listID,
            $this->url()->fromRoute('reservationlist-deletebulk'),
            $this->url()->fromRoute('reservationlist-home'),
            'ReservationList::confirm_delete_bulk',
            [],
            [
                'listID' => $listID,
                'ids' => $ids,
            ]
        );
    }

    /**
     * Confirm a request to delete a reservation item.
     *
     * @param string       $id          ID of object to delete
     * @param string       $url         URL to return to if deletion is confirmed
     * @param string       $fallbackUrl URL to return to if deletion is not confirmed
     * @param string       $title       Title of the confirmation dialog
     * @param string|array $messages    Message key for the confirmation dialog
     * @param array        $extras      Additional parameters to pass to the confirmation dialog
     *
     * @return mixed
     */
    protected function confirmDelete(
        $id,
        $url,
        $fallbackUrl,
        $title = 'ReservationList::confirm_delete',
        $messages = 'confirm_delete',
        $extras = []
    ) {
        if (empty($id)) {
            $url = $fallbackUrl;
        }
        return $this->confirm(
            $title,
            $url,
            $url,
            $messages,
            $extras
        );
    }

    /**
     * Home action for the ReservationListController.
     *
     * @return \Laminas\View\Model\ViewModel
     */
    public function homeAction()
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->forceLogin();
        }
        $lists = $this->reservationListService->getReservationListsForUser($user);
        $view = $this->createViewModel(
            ['lists' => $lists]
        );
        return $view;
    }

    /**
     * Retrieves the request as an array.
     *
     * @return array Request as an array.
     */
    protected function getRequestAsArray(): array
    {
        $request = $this->getRequest()->getQuery()->toArray()
          + $this->getRequest()->getPost()->toArray();

        if (!null !== $this->params()->fromRoute('id')) {
            $request += ['id' => $this->params()->fromRoute('id')];
        }
        return $request;
    }

    /**
     * Retrieves list of reservations as results.
     *
     * @param mixed $request The request object.
     *
     * @return \VuFind\Search\Base\Results
     */
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
