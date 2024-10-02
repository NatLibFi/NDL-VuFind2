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
            $request = $this->getRequest();
            $request->getPost()->set('datasource', $driver->getDatasource())->set('institution', $institution);
            if ('saveList' === $state) {
                if (!$title) {
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
                    $request->getPost()
                );
            } elseif ('saveItem' === $state) {
                $this->reservationListService->saveRecordToReservationList(
                    $request->getPost(),
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
        try {
            $list = $this->reservationListService->getListAndDetailsByListId(
                $this->getParam('id'),
                $user
            );
        } catch (RecordMissingException $e) {
            return $this->redirect()->toRoute('reservationlist-home');
        }
        $results = $this->getListAsResults();
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
     * Handles ordering of reservation lists
     *
     * @return mixed
     */
    public function orderAction()
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->forceLogin();
        }
        $request = $this->getRequest();
        $listId = $this->params()->fromPost(
            'rl_list_id',
            $this->params()->fromQuery(
                'id',
                $this->params()->fromRoute('id')
            )
        );

        // Check that list is not ordered or deleted
        $list = $this->reservationListService->getListAndDetailsByListId($listId, $user);
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
        $formId = Form::RESERVATION_LIST_REQUEST;

        $resourcesText = '';
        foreach ($this->reservationListService->getResourcesForList($list['list_entity'], $user) as $resource) {
            $resourcesText .= $resource->getRecordId() . '||' . $resource->getTitle() . PHP_EOL;
        }
        // Set reservationlist specific form values
        $request->getPost()
            ->set('rl_list_id', $listId)
            ->set('rl_institution', $details->getInstitution())
            ->set('rl_list_identifier', $details->getListConfigIdentifier())
            ->set('record_ids', $resourcesText);

        $form = $this->getService(\Finna\Form\Form::class);
        $params = [];
        if ($refererHeader = $this->getRequest()->getHeader('Referer')) {
            $params['referrer'] = $refererHeader->getFieldValue();
        }
        if ($userAgentHeader = $this->getRequest()->getHeader('User-Agent')) {
            $params['userAgent'] = $userAgentHeader->getFieldValue();
        }
        $form->setFormId($formId, $params, $request->getPost()->toArray());

        if (!$form->isEnabled()) {
            throw new \VuFind\Exception\Forbidden("Form '$formId' is disabled");
        }

        $view = $this->createViewModel(compact('form', 'formId', 'user'));
        $view->setTemplate('feedback/form');
        $view->useCaptcha = false;

        $params = $this->params();
        $form->setData($request->getPost()->toArray());
        if (!$this->formWasSubmitted(useCaptcha: false)) {
            $form->setData(
                [
                 'name' => $user->getFirstname() . ' ' . $user->getLastname(),
                 'email' => $user->getEmail(),
                ]
            );
            return $view;
        }

        if (!$form->isValid()) {
            return $view;
        }

        // Override recipients to match lists configured recipients:
        $request->getPost()->set('recipient', $configuration['Recipient']);
        $primaryHandler = $form->getPrimaryHandler();
        $success = $primaryHandler->handle($form, $params, $user);
        if ($success) {
            $this->flashMessenger()->addSuccessMessage($form->getSubmitResponse());
            $this->reservationListService->setListOrdered($user, $list['list_entity'], $request->getPost());
            return $this->getRefreshResponse();
        } else {
            $this->flashMessenger()->addErrorMessage(
                $this->translate('could_not_process_feedback')
            );
        }
        return $view;
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
            'confirm_delete_list_brief',
            'confirm_delete_list_text',
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
            'confirm_delete_brief',
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
     * @return \VuFind\Search\Base\Results
     */
    protected function getListAsResults()
    {
        $request = $this->getRequestAsArray();
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
