<?php

/**
 * Favorites service
 *
 * PHP version 8
 *
 * Copyright (C) Villanova University 2016.
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
 * @package  Favorites
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org Main Page
 */

namespace Finna\ReservationList;

use DateTime;
use Finna\Db\Entity\FinnaResourceListDetailsEntityInterface;
use Finna\Db\Entity\FinnaResourceListEntityInterface;
use Finna\Db\Service\FinnaResourceListDetailsServiceInterface;
use Finna\Db\Service\FinnaResourceListResourceServiceInterface;
use Finna\Db\Service\FinnaResourceListServiceInterface;
use Laminas\Session\Container;
use Laminas\Stdlib\Parameters;
use VuFind\Db\Entity\ResourceEntityInterface;
use VuFind\Db\Entity\UserEntityInterface;
use VuFind\Db\Service\DbServiceAwareInterface;
use VuFind\Db\Service\DbServiceAwareTrait;
use VuFind\Db\Service\ResourceServiceInterface;
use VuFind\Db\Service\UserServiceInterface;
use VuFind\Exception\ListPermission as ListPermissionException;
use VuFind\Exception\LoginRequired as LoginRequiredException;
use VuFind\Exception\MissingField as MissingFieldException;
use VuFind\I18n\Translator\TranslatorAwareInterface;
use VuFind\Record\Cache as RecordCache;
use VuFind\Record\Loader as RecordLoader;
use VuFind\Record\ResourcePopulator;
use VuFind\RecordDriver\AbstractBase as RecordDriver;
use VuFind\RecordDriver\DefaultRecord;

use function in_array;

/**
 * Favorites service
 *
 * @category VuFind
 * @package  Favorites
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org Main Page
 */
class ReservationListService implements TranslatorAwareInterface, DbServiceAwareInterface
{
    use \VuFind\I18n\Translator\TranslatorAwareTrait;
    use DbServiceAwareTrait;

    /**
     * Type of resource list
     *
     * @var string
     */
    public const RESOURCE_LIST_TYPE = 'reservationlist';

    /**
     * Constructor
     *
     * @param FinnaResourceListServiceInterface         $resourceListService         Resource list database service
     * @param FinnaResourceListResourceServiceInterface $resourceListResourceService Resource and list relation
     *                                                                               database service
     * @param ResourceServiceInterface                  $resourceService             Resource database service
     * @param UserServiceInterface                      $userService                 User database service
     * @param FinnaResourceListDetailsServiceInterface  $resourceListDetailsService  Resource list details service
     * @param ResourcePopulator                         $resourcePopulator           Resource populator service
     * @param RecordLoader                              $recordLoader                Record loader
     * @param ?RecordCache                              $recordCache                 Record cache (optional)
     * @param ?Container                                $session                     Session container for remembering
     *                                                                               state (optional)
     */
    public function __construct(
        protected FinnaResourceListServiceInterface $resourceListService,
        protected FinnaResourceListResourceServiceInterface $resourceListResourceService,
        protected ResourceServiceInterface $resourceService,
        protected UserServiceInterface $userService,
        protected FinnaResourceListDetailsServiceInterface $resourceListDetailsService,
        protected ResourcePopulator $resourcePopulator,
        protected RecordLoader $recordLoader,
        protected ?RecordCache $recordCache = null,
        protected ?Container $session = null
    ) {
    }

    /**
     * Create a new list object for the specified user and details
     *
     * @param ?UserEntityInterface $user Logged in user (null if logged out)
     *
     * @return FinnaResourceListEntityInterface
     * @throws LoginRequiredException
     */
    public function createListForUser(?UserEntityInterface $user): array
    {
        if (!$user) {
            throw new LoginRequiredException('Log in to create lists.');
        }

        $list = $this->resourceListService->createEntity()
            ->setUser($user)
            ->setCreated(new DateTime());

        $details = $this->resourceListDetailsService->createEntity()
            ->setListType(self::RESOURCE_LIST_TYPE);
        return ['list_entity' => $list, 'details_entity' => $details];
    }

    /**
     * Destroy a list.
     *
     * @param FinnaResourceListEntityInterface $list  List to destroy
     * @param ?UserEntityInterface             $user  Logged-in user (null if none)
     * @param bool                             $force Should we force the delete without checking permissions?
     *
     * @return void
     * @throws ListPermissionException
     */
    public function destroyList(
        FinnaResourceListEntityInterface $list,
        ?UserEntityInterface $user = null,
        bool $force = false
    ): void {
        if (!$force && !$this->userCanEditList($user, $list)) {
            throw new ListPermissionException('list_access_denied');
        }

        // Remove user_resource and resource_tags rows for favorites tags:
        $listUser = $list->getUser();
        $this->resourceListResourceService->unlinkResources(null, $listUser, $list);
        $this->resourceListService->deleteFinnaResourceList($list);
    }

    /**
     * Remember that this list was used so that it can become the default in
     * dialog boxes.
     *
     * @param FinnaResourceListEntityInterface $list List to remember
     *
     * @return void
     */
    public function rememberLastUsedList(FinnaResourceListEntityInterface $list): void
    {
        if (null !== $this->session) {
            $this->session->lastUsed = $list->getId();
        }
    }

    /**
     * Get a list object for the specified ID (or null to create a new list).
     * Ensure that the object is persisted to the database if it does not
     * already exist, and remember it as the user's last-accessed list.
     *
     * @param ?int                $listId List ID (or null to create a new list)
     * @param UserEntityInterface $user   The user saving the record
     *
     * @return FinnaResourceListEntityInterface
     *
     * @throws \VuFind\Exception\ListPermission
     */
    public function getAndRememberListObject(?int $listId, UserEntityInterface $user): FinnaResourceListEntityInterface
    {
        $list = $this->resourceListService->getResourceListById($listId);
        // Validate incoming list ID:
        if (!$this->userCanEditList($user, $list)) {
            throw new \VuFind\Exception\ListPermission('Access denied.');
        }
        $this->rememberLastUsedList($list); // handled by saveListForUser() in other case
        return $list;
    }

    /**
     * Persist a resource to the record cache (if applicable).
     *
     * @param RecordDriver            $driver   Record driver to persist
     * @param ResourceEntityInterface $resource Resource row
     *
     * @return void
     */
    protected function persistToCache(
        RecordDriver $driver,
        ResourceEntityInterface $resource
    ) {
        if ($this->recordCache) {
            $this->recordCache->setContext(RecordCache::CONTEXT_FAVORITE);
            $this->recordCache->createOrUpdate(
                $resource->getRecordId(),
                $resource->getSource(),
                $driver->getRawData()
            );
        }
    }

    /**
     * Given an array of item ids, remove them from the specified list.
     *
     * @param FinnaResourceListEntityInterface $list   List being updated
     * @param ?UserEntityInterface             $user   Logged-in user (null if none)
     * @param string[]                         $ids    IDs to remove from the list
     * @param string                           $source Type of resource identified by IDs
     *
     * @return void
     */
    public function removeListResourcesById(
        FinnaResourceListEntityInterface $list,
        ?UserEntityInterface $user,
        array $ids,
        string $source = DEFAULT_SEARCH_BACKEND
    ): void {
        if (!$this->userCanEditList($user, $list)) {
            throw new ListPermissionException('list_access_denied');
        }

        // Retrieve a list of resource IDs:
        $resources = $this->resourceService->getResourcesByRecordIds($ids, $source);

        $resourceIDs = [];
        foreach ($resources as $current) {
            $resourceIDs[] = $current->getId();
        }

        // Remove Resource and related tags:
        $listUser = $list->getUser();
        $this->resourceListResourceService->unlinkResources($resourceIDs, $listUser, $list);
    }

    /**
     * Given an array of item ids, remove them from all of the specified user's lists
     *
     * @param UserEntityInterface $user   User owning lists
     * @param string[]            $ids    IDs to remove from the list
     * @param string              $source Type of resource identified by IDs
     *
     * @return void
     */
    public function removeUserResourcesById(
        UserEntityInterface $user,
        array $ids,
        $source = DEFAULT_SEARCH_BACKEND
    ): void {
        // Retrieve a list of resource IDs:
        $resources = $this->resourceService->getResourcesByRecordIds($ids, $source);

        $resourceIDs = [];
        foreach ($resources as $current) {
            $resourceIDs[] = $current->getId();
        }
        $this->resourceListResourceService->unlinkResources($resourceIDs, $user->getId(), null);
    }

    /**
     * Add/update a resource in the user's account.
     *
     * @param UserEntityInterface|int              $userOrId     The user entity or ID saving the favorites
     * @param ResourceEntityInterface|int          $resourceOrId The resource entity or ID to add/update
     * @param FinnaResourceListEntityInterface|int $listOrId     The list entity or ID to store the resource in.
     * @param string                               $notes        User notes about the resource.
     *
     * @return void
     */
    public function saveResourceToReservationList(
        UserEntityInterface|int $userOrId,
        ResourceEntityInterface|int $resourceOrId,
        FinnaResourceListEntityInterface|int $listOrId,
        string $notes,
    ): void {
        $user = $userOrId instanceof UserEntityInterface
            ? $userOrId
            : $this->userService->getUserById($userOrId);
        $resource = $resourceOrId instanceof ResourceEntityInterface
            ? $resourceOrId
            : $this->resourceService->getResourceById($resourceOrId);
        $list = $listOrId instanceof FinnaResourceListEntityInterface
            ? $listOrId
            : $this->resourceListService->getResourceListById($listOrId);

        // Create the resource link if it doesn't exist and update the notes in any
        // case:
        $this->resourceListResourceService->createOrUpdateLink($resource, $user, $list, $notes);
    }

    /**
     * Save this record to a resource list.
     *
     * @param Parameters          $params Array with some or all of these keys:
     *                                    <ul> <li>mytags - Tag array to
     *                                    associate with record (optional)</li>
     *                                    <li>notes - Notes to associate with
     *                                    record (optional)</li> <li>list - ID
     *                                    of list to save record into (omit to
     *                                    create new list)</li> </ul>
     * @param UserEntityInterface $user   The user saving the record
     * @param RecordDriver        $driver Record driver for record being saved
     *
     * @return array list information
     */
    public function saveRecordToResourceList(
        Parameters $params,
        UserEntityInterface $user,
        RecordDriver $driver
    ): array {
        // Validate incoming parameters:
        if (!$user) {
            throw new LoginRequiredException('You must be logged in first');
        }

        // Get or create a list object as needed:
        $list = $this->getAndRememberListObject($params->get('list', 'NEW'), $user);

        // Get or create a resource object as needed:
        $resource = $this->resourcePopulator->getOrCreateResourceForDriver($driver);

        // Persist record in the database for "offline" use
        $this->persistToCache($driver, $resource);

        // Add the information to the user's account:
        $this->saveResourceToReservationList(
            $user,
            $resource,
            $list,
            $params->get('desc', '')
        );
        return ['listId' => $list->getId()];
    }

    /**
     * Set list ordered
     *
     * @param UserEntityInterface $user       User to check for rights to list
     * @param int                 $listId     Id of the list to set ordered
     * @param string              $pickupDate Date for the order to be picked up
     *
     * @return bool
     */
    public function setListOrdered(UserEntityInterface $user, int $listId, string $pickupDate): bool
    {
        try {
            $list = $this->resourceListService->getResourceListById($listId);
            if (!$this->userCanEditList($user, $list)) {
                throw new ListPermissionException('list_access_denied');
            }
            $details = $this->resourceListDetailsService->getFinnaResourceListDetailsById($list);
            $details->setPickupDate(DateTime::createFromFormat('Y-m-d H:i:s', $pickupDate))->setOrdered();
            $this->resourceListDetailsService->persistEntity($details);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Saves the provided list to the database and remembers it in the session if it is valid;
     * throws an exception otherwise.
     *
     * @param FinnaResourceListEntityInterface $list List to save
     * @param ?UserEntityInterface             $user Logged-in user (null if none)
     *
     * @return void
     * @throws ListPermissionException
     * @throws MissingFieldException
     */
    public function saveListForUser(
        FinnaResourceListEntityInterface $list,
        ?UserEntityInterface $user
    ): void {
        if (!$this->userCanEditList($user, $list)) {
            throw new ListPermissionException('list_access_denied');
        }
        if (!$list->getTitle()) {
            throw new MissingFieldException('list_edit_name_required');
        }
        $this->resourceListService->persistEntity($list);
        $this->rememberLastUsedList($list);
    }

    /**
     * Saves the provided list to the database and remembers it in the session if it is valid;
     * throws an exception otherwise.
     *
     * @param FinnaResourceListEntityInterface        $list    List to save
     * @param FinnaResourceListDetailsEntityInterface $details List details to update
     * @param ?UserEntityInterface                    $user    Logged-in user (null if none)
     *
     * @return void
     * @throws ListPermissionException
     * @throws MissingFieldException
     */
    public function saveDetailsForList(
        FinnaResourceListEntityInterface $list,
        FinnaResourceListDetailsEntityInterface $details,
        ?UserEntityInterface $user
    ): void {
        if (!$this->userCanEditList($user, $list)) {
            throw new ListPermissionException('list_access_denied');
        }
        $this->resourceListDetailsService->persistEntity($details);
    }

    /**
     * Update and save the list object using a request object -- useful for
     * sharing form processing between multiple actions.
     *
     * @param FinnaResourceListEntityInterface        $list    List to update
     * @param FinnaResourceListDetailsEntityInterface $details List details to update
     * @param ?UserEntityInterface                    $user    Logged-in user (false if none)
     * @param Parameters                              $request Request to process
     *
     * @return int ID of newly created row
     * @throws ListPermissionException
     * @throws MissingFieldException
     */
    public function updateListFromRequest(
        FinnaResourceListEntityInterface $list,
        FinnaResourceListDetailsEntityInterface $details,
        ?UserEntityInterface $user,
        Parameters $request
    ): int {
        try {
            $this->resourceListService->beginTransaction();
            $list->setTitle($request->get('title'))->setDescription($request->get('desc'));
            $this->saveListForUser($list, $user);
            $details->setInstitution($request->get('institution'))
                ->setListConfigIdentifier($request->get('listIdentifier'))
                ->setUserId($user->getId())
                ->setListId($list->getId())
                ->setListType(self::RESOURCE_LIST_TYPE)
                ->setConnection($request->get('connection', 'database'));
            $this->saveDetailsForList($list, $details, $user);
            $this->resourceListService->commitTransaction();
        } catch (\Exception $e) {
            $this->resourceListService->rollbackTransaction();
        }

        return $list->getId();
    }

    /**
     * Is the provided user allowed to edit the provided list?
     *
     * @param ?UserEntityInterface             $user Logged-in user (null if none)
     * @param FinnaResourceListEntityInterface $list List to check
     *
     * @return bool
     */
    public function userCanEditList(?UserEntityInterface $user, FinnaResourceListEntityInterface $list): bool
    {
        return $user && $user->getId() === $list->getUser()?->getId();
    }

    /**
     * Support method for saveBulk() -- save a batch of records to the cache.
     *
     * @param array $cacheRecordIds Array of IDs in source|id format
     *
     * @return void
     */
    protected function cacheBatch(array $cacheRecordIds)
    {
        if ($cacheRecordIds && $this->recordCache) {
            // Disable the cache so that we fetch latest versions, not cached ones:
            $this->recordLoader->setCacheContext(RecordCache::CONTEXT_DISABLED);
            $records = $this->recordLoader->loadBatch($cacheRecordIds);
            // Re-enable the cache so that we actually save the records:
            $this->recordLoader->setCacheContext(RecordCache::CONTEXT_FAVORITE);
            foreach ($records as $record) {
                $this->recordCache->createOrUpdate(
                    $record->getUniqueID(),
                    $record->getSourceIdentifier(),
                    $record->getRawData()
                );
            }
        }
    }

    /**
     * Delete a group of resources.
     *
     * @param string[]            $ids    Array of IDs in source|id format.
     * @param ?int                $listID ID of list to delete from (null for all lists)
     * @param UserEntityInterface $user   Logged in user
     *
     * @return void
     */
    public function deleteResourcesFromList(array $ids, ?int $listID, UserEntityInterface $user): void
    {
        // Sort $ids into useful array:
        $sorted = [];
        foreach ($ids as $current) {
            [$source, $id] = explode('|', $current, 2);
            if (!isset($sorted[$source])) {
                $sorted[$source] = [];
            }
            $sorted[$source][] = $id;
        }

        // Delete favorites one source at a time, using a different object depending
        // on whether we are working with a list or user favorites.
        if (empty($listID)) {
            foreach ($sorted as $source => $ids) {
                $this->removeUserResourcesById($user, $ids, $source);
            }
        } else {
            $list = $this->resourceListService->getResourceListById($listID);
            foreach ($sorted as $source => $ids) {
                $this->removeListResourcesById($list, $user, $ids, $source);
            }
        }
    }

    /**
     * Get resource list as an array containing formatted dates to be displayed in templates
     *
     * @param int                          $listId   List id
     * @param UserEntityInterface|int|null $userOrId User entity object or ID
     *
     * @return array [list_entity, details_entity]
     */
    public function getListAndDetailsByListId(
        int $listId,
        UserEntityInterface|int|null $userOrId = null
    ): array {
        $list = $this->resourceListService->getFinnaResourceListById($listId);
        $user = $userOrId instanceof UserEntityInterface
            ? $userOrId
            : $this->userService->getUserById($userOrId);
        // Validate incoming list ID:
        if (!$this->userCanEditList($user, $list)) {
            throw new \VuFind\Exception\ListPermission('Access denied.');
        }
        $details = $this->resourceListDetailsService->getFinnaResourceListDetailsById(
            $list->getId(),
            self::RESOURCE_LIST_TYPE
        );
        return ['list_entity' => $list, 'details_entity' => $details];
    }

    /**
     * Get resource lists identified as reservation list for user
     *
     * @param UserEntityInterface|int|null $userOrId       Optional user ID or entity object (to limit results
     *                                                     to a particular user).
     * @param string                       $institution    List institution
     * @param string                       $listIdentifier List identifier given by the institution
     *                                                     for the list or empty for all
     *
     * @return FinnaResourceListEntityInterface[]
     */
    public function getReservationListsForUser(
        UserEntityInterface|int $userOrId,
        string $institution = '',
        string $listIdentifier = ''
    ): array {
        $settings = $this->resourceListDetailsService->getFinnaResourceListDetailsByUser(
            $userOrId,
            $listIdentifier,
            $institution,
            self::RESOURCE_LIST_TYPE
        );
        $result = [];
        foreach ($settings as $detail) {
            $result[] = [
                'list_entity' => $this->resourceListService->getResourceListById($detail->getListId()),
                'details_entity' => $detail,
            ];
        }
        return $result;
    }

    /**
     * Get list details
     *
     * @param FinnaResourceListEntityInterface|int $listOrId List id
     *
     * @return FinnaResourceListDetailsEntityInterface
     */
    public function getListDetails(
        FinnaResourceListEntityInterface|int $listOrId
    ): FinnaResourceListDetailsEntityInterface {
        $id = $listOrId instanceof FinnaResourceListEntityInterface
            ? $listOrId->getId()
            : $listOrId;
        return $this->resourceListDetailsService->getFinnaResourceListDetailsById($id, self::RESOURCE_LIST_TYPE);
    }

    /**
     * Get lists not containing a specific record.
     *
     * @param UserEntityInterface|int        $userOrId       User or id to check for lists
     * @param ResourceEntityInterface|string $recordOrId     ID of record being checked
     * @param string                         $source         $source Record search backend
     * @param string                         $listIdentifier List identifier in list config
     * @param string                         $institution    Institution in the list details
     *
     * @return array
     */
    public function getListsNotContainingRecord(
        UserEntityInterface|int $userOrId,
        DefaultRecord|string $recordOrId,
        string $source = DEFAULT_SEARCH_BACKEND,
        string $listIdentifier = '',
        string $institution = ''
    ): array {
        $recordId = $recordOrId instanceof RecordDriver
            ? $recordOrId->getUniqueID()
            : $recordOrId;
        $details = $this->resourceListDetailsService->getFinnaResourceListDetailsByUser(
            $userOrId,
            $listIdentifier,
            $institution,
            self::RESOURCE_LIST_TYPE
        );
        $result = [];
        $listIds = array_map(
            fn ($list) => $list->getId(),
            $this->resourceListService->getListsContainingRecord($recordId, $source, $userOrId)
        );
        foreach ($details as $detail) {
            if (in_array($detail->getListId(), $listIds)) {
                continue;
            }
            $list = $this->resourceListService->getFinnaResourceListById($detail->getListId());
            $result[] = [
                'list_entity' => $list,
                'details_entity' => $detail,
            ];
        }
        return $result;
    }

    /**
     * Get resources for list
     *
     * @param FinnaResourceListEntityInterface $list List to get resources for
     * @param UserEntityInterface              $user User entity
     *
     * @return array
     */
    public function getResourcesForList(
        FinnaResourceListEntityInterface $list,
        UserEntityInterface $user
    ): array {
        return $this->resourceListResourceService->getResourcesForList($user, $list);
    }

    /**
     * Get lists containing a specific record.
     *
     * @param DefaultRecord|string         $recordOrId Record or ID of record being checked.
     * @param string                       $source     Source of record to look up
     * @param UserEntityInterface|int|null $userOrId   Optional user ID or entity object (to limit results
     *                                                 to a particular user).
     *
     * @return FinnaResourceListEntityInterface[]
     */
    public function getListsContainingRecord(
        DefaultRecord|string $recordOrId,
        string $source = DEFAULT_SEARCH_BACKEND,
        UserEntityInterface|int|null $userOrId = null
    ): array {
        return $this->resourceListService->getListsContainingRecord($recordOrId, $source, $userOrId);
    }
}
