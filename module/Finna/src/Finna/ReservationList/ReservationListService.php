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
use Finna\Db\Entity\FinnaResourceListEntityInterface;
use Finna\Db\Service\FinnaResourceListResourceServiceInterface;
use Finna\Db\Service\FinnaResourceListServiceInterface;
use Laminas\Session\Container;
use Laminas\Stdlib\Parameters;
use VuFind\Db\Entity\ResourceEntityInterface;
use VuFind\Db\Entity\UserEntityInterface;
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
use VuFind\Tags\TagsService;

use function intval;

/**
 * Favorites service
 *
 * @category VuFind
 * @package  Favorites
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org Main Page
 */
class ReservationListService implements TranslatorAwareInterface
{
    use \VuFind\I18n\Translator\TranslatorAwareTrait;

    /**
     * Constructor
     *
     * @param FinnaResourceListServiceInterface         $resourceListService         Resource list database service
     * @param FinnaResourceListResourceServiceInterface $resourceListResourceService Resource list database service
     * @param ResourceServiceInterface                  $resourceService             Resource database service
     * @param UserServiceInterface                      $userService                 User database service
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
        protected ResourcePopulator $resourcePopulator,
        protected RecordLoader $recordLoader,
        protected ?RecordCache $recordCache = null,
        protected ?Container $session = null
    ) {
    }

    /**
     * Create a new list object for the specified user.
     *
     * @param ?UserEntityInterface $user Logged in user (null if logged out)
     *
     * @return FinnaResourceListEntityInterface
     * @throws LoginRequiredException
     */
    public function createListForUser(?UserEntityInterface $user): FinnaResourceListEntityInterface
    {
        if (!$user) {
            throw new LoginRequiredException('Log in to create lists.');
        }

        return $this->resourceListService->createEntity()
            ->setUser($user)
            ->setCreated(new DateTime());
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
        $this->resourceListResourceService->unlinkFavorites(null, $listUser, $list);
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
        if (empty($listId)) {
            $list = $this->createListForUser($user)
                ->setTitle($this->translate('default_list_title'));
            $this->saveListForUser($list, $user);
        } else {
            $list = $this->resourceListService->getResourceListById($listId);
            // Validate incoming list ID:
            if (!$this->userCanEditList($user, $list)) {
                throw new \VuFind\Exception\ListPermission('Access denied.');
            }
            $this->rememberLastUsedList($list); // handled by saveListForUser() in other case
        }
        return $list;
    }

    /**
     * Given an array of parameters, extract a list ID if possible. Return null
     * if no valid ID is found or if a "NEW" record is requested.
     *
     * @param array $params Parameters to process
     *
     * @return ?int
     */
    public function getListIdFromParams(array $params): ?int
    {
        return intval($params['list'] ?? 'NEW') ?: null;
    }

    /**
     * Retrieve the ID of the last list that was accessed, if any.
     *
     * @return ?int Identifier value of a FinnaResourceListEntityInterface object (if set) or null (if not available).
     */
    public function getLastUsedList(): ?int
    {
        return $this->session->lastUsed ?? null;
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
        $this->resourceListResourceService->unlinkFavorites($resourceIDs, $listUser, $list);
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
        $this->resourceListResourceService->unlinkFavorites($resourceIDs, $user->getId(), null);
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
    public function saveResourceToFavorites(
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
     * Save this record to the user's favorites.
     *
     * @param array               $params Array with some or all of these keys:
     *  <ul>
     *    <li>mytags - Tag array to associate with record (optional)</li>
     *    <li>notes - Notes to associate with record (optional)</li>
     *    <li>list - ID of list to save record into (omit to create new list)</li>
     *  </ul>
     * @param UserEntityInterface $user   The user saving the record
     * @param RecordDriver        $driver Record driver for record being saved
     *
     * @return array list information
     */
    public function saveRecordToResourceList(
        array $params,
        UserEntityInterface $user,
        RecordDriver $driver
    ): array {
        // Validate incoming parameters:
        if (!$user) {
            throw new LoginRequiredException('You must be logged in first');
        }

        // Get or create a list object as needed:
        $list = $this->getAndRememberListObject($this->getListIdFromParams($params), $user);

        // Get or create a resource object as needed:
        $resource = $this->resourcePopulator->getOrCreateResourceForDriver($driver);

        // Persist record in the database for "offline" use
        $this->persistToCache($driver, $resource);

        // Add the information to the user's account:
        $this->saveResourceToFavorites(
            $user,
            $resource,
            $list,
            $params['notes'] ?? ''
        );
        return ['listId' => $list->getId()];
    }

    /**
     * Set reservation list ordered timestamp
     *
     * @param
     */

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
    public function saveListForUser(FinnaResourceListEntityInterface $list, ?UserEntityInterface $user): void
    {
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
     * Add a tag to a list.
     *
     * @param string                           $tagText The tag to save.
     * @param FinnaResourceListEntityInterface $list    The list being tagged.
     * @param UserEntityInterface              $user    The user posting the tag.
     *
     * @return void
     */
    public function addListTag(string $tagText, FinnaResourceListEntityInterface $list, UserEntityInterface $user): void
    {
    }

    /**
     * Update and save the list object using a request object -- useful for
     * sharing form processing between multiple actions.
     *
     * @param FinnaResourceListEntityInterface $list    List to update
     * @param ?UserEntityInterface             $user    Logged-in user (false if none)
     * @param Parameters                       $request Request to process
     *
     * @return int ID of newly created row
     * @throws ListPermissionException
     * @throws MissingFieldException
     */
    public function updateListFromRequest(
        FinnaResourceListEntityInterface $list,
        ?UserEntityInterface $user,
        Parameters $request
    ): int {
        $list->setTitle($request->get('title'))
            ->setDatasource($request->get('datasource'))
            ->setBuilding($request->get('building'))
            ->setDescription($request->get('desc'));
        $this->saveListForUser($list, $user);

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
     * Save a group of records to the user's favorites.
     *
     * @param array               $params Array with some or all of these keys:
     *                                    <ul> <li>ids - Array of IDs in
     *                                    source|id format</li> <li>mytags -
     *                                    Unparsed tag string to associate with
     *                                    record (optional)</li> <li>list - ID
     *                                    of list to save record into (omit to
     *                                    create new list)</li> </ul>
     * @param UserEntityInterface $user   The user saving the record
     *
     * @return array list information
     */
    public function saveRecordsToResourceList(array $params, UserEntityInterface $user): array
    {
        // Load helper objects needed for the saving process:
        $list = $this->getAndRememberListObject($this->getListIdFromParams($params), $user);
        $this->recordCache?->setContext(RecordCache::CONTEXT_FAVORITE);

        $cacheRecordIds = [];   // list of record IDs to save to cache
        foreach ($params['ids'] as $current) {
            // Break apart components of ID:
            [$source, $id] = explode('|', $current, 2);

            // Get or create a resource object as needed:
            $resource = $this->resourcePopulator->getOrCreateResourceForRecordId($id, $source);

            $this->saveResourceToFavorites($user, $resource, $list, '', false);

            // Collect record IDs for caching
            if ($this->recordCache?->isCachable($resource->getSource())) {
                $cacheRecordIds[] = $current;
            }
        }

        $this->cacheBatch($cacheRecordIds);
        return ['listId' => $list->getId()];
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
     * @param FinnaResourceListEntityInterface|int|null $listOrId List to change into an array containing
     *                                                            keys as objects keys and values
     *
     * @return array<string,string|int>
     */
    public function getResourceListAsFormattedArray(FinnaResourceListEntityInterface|int|null $listOrId): array
    {
        $list = $listOrId instanceof FinnaResourceListEntityInterface
            ? $listOrId
            : $this->resourceListService->getFinnaResourceListById($listOrId);
        return $list->toArray();
    }

    /**
     * Call TagsService::getUserTagsFromFavorites() and format the results for editing.
     *
     * @param UserEntityInterface|int                   $userOrId User ID to look up.
     * @param FinnaResourceListEntityInterface|int|null $listOrId Filter for tags tied to a specific list
     * (null for no
     * filter).
     * @param ?string                                   $recordId Filter for tags tied to a specific resource
     * (null for no
     *                                                            filter).
     * @param ?string                                   $source   Filter for tags tied to a specific record
     * source (null for
     *                                                            no filter).
     *
     * @return string
     */
    public function getTagStringForEditing(
        UserEntityInterface|int $userOrId,
        FinnaResourceListEntityInterface|int|null $listOrId = null,
        ?string $recordId = null,
        ?string $source = null
    ): string {
        return '';
    }

    /**
     * Convert an array representing tags into a string for an edit form
     *
     * @param array $tags Tags
     *
     * @return string
     */
    public function formatTagStringForEditing($tags): string
    {
        return '';
    }

    /**
     * Get resource lists identified as reservation list for user
     *
     * @param UserEntityInterface|int|null $userOrId Optional user ID or entity object (to limit results
     * to a particular user).
     *
     * @return FinnaResourceListEntityInterface[]
     */
    public function getReservationListsForUser(UserEntityInterface|int $userOrId)
    {
        return $this->resourceListService->getResourceListsByUser($userOrId);
    }

    /**
     * Get lists not containing a specific record.
     *
     * @param UserEntityInterface $user     User to check for lists
     * @param string              $recordId ID of record being checked
     * @param string              $source   Source of record to look up
     *
     * @return FinnaResourceListEntityInterface[]
     */
    public function getListsNotContainingRecord(
        UserEntityInterface $user,
        string $recordId,
        string $source = DEFAULT_SEARCH_BACKEND,
    ): array {
        return $this->resourceListService->getListsNotContainingRecord($recordId, $source, $user);
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
