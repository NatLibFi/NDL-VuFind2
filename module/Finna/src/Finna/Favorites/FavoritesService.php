<?php

/**
 * Service for modifying User Lists.
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
 * along with this program; if not, see
 * <https://www.gnu.org/licenses/>.
 *
 * Favorites service
 *
 * @category VuFind
 * @package  Favorites
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @author   Minna Rönkä <minna.ronka@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org Main Page
 */

namespace Finna\Favorites;

use DateTime;
use Finna\Db\Entity\UserListEntityInterface;
use Finna\Db\Service\UserListService;
use Finna\Db\Service\UserResourceService;
use VuFind\Db\Entity\ResourceEntityInterface;
use VuFind\Db\Entity\UserEntityInterface;
use VuFind\Db\Entity\UserListEntityInterface as VuFindUserListEntityInterface;
use VuFind\Exception\LoginRequired as LoginRequiredException;
use VuFind\Record\Cache as RecordCache;

use function assert;

/**
 *  Favorites service.
 *
 * @category VuFind
 * @package  Favorites
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @author   Minna Rönkä <minna.ronka@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org Main Page
 */
class FavoritesService extends \VuFind\Favorites\FavoritesService
{
    /**
     * Save a group of records to the user's favorites.
     *
     * Finna: Handle custom order. Do not overwrite existing notes.
     *
     * @param array               $params  Array with some or all of these keys:
     *                                     <ul> <li>ids - Array of IDs in
     *                                     source|id format</li> <li>mytags -
     *                                     Unparsed tag string to associate with
     *                                     record (optional)</li> <li>list - ID
     *                                     of list to save record into (omit to
     *                                     create new list)</li> </ul>
     * @param UserEntityInterface $user    The user saving the record
     * @param array               $records Record drivers
     *
     * @return array list information
     */
    public function saveMany(array $params, UserEntityInterface $user, array $records): array
    {
        // Load helper objects needed for the saving process:
        $list = $this->getAndRememberListObject($this->getListIdFromParams($params), $user);
        $this->recordCache?->setContext(RecordCache::CONTEXT_FAVORITE);

        assert($this->userListService instanceof UserListService);
        assert($this->userResourceService instanceof UserResourceService);

        // Add custom order keys for new items if the list has custom order:
        $index = $this->userListService->getNextAvailableCustomOrderIndex($list) ?: null;

        // If target list is not in custom order then reverse the records to get original order:
        if (!$this->userListService->isCustomOrderAvailable($list)) {
            $records = array_reverse($records);
        }
        $tags = isset($params['mytags']) ? $this->tagsService->parse($params['mytags']) : [];

        foreach ($records as $record) {
            // Get or create a resource object as needed:
            $resource = $this->resourcePopulator->getOrCreateResourceForDriver($record);

            // Create the resource link if it doesn't exist; do not overwrite existing notes:
            $this->userResourceService->createOrUpdateLinkWithOrder($resource, $user, $list, null, $index);
            // Increase custon order index if in use:
            if (null !== $index) {
                ++$index;
            }

            // Add the new tags:
            foreach ($tags as $tag) {
                $this->tagsService->linkTagToResource($tag, $resource, $user, $list);
            }

            // Cache record:
            if ($this->recordCache?->isCachable($resource->getSource())) {
                $this->recordCache->createOrUpdate(
                    $record->getUniqueID(),
                    $record->getSourceIdentifier(),
                    $record->getRawData()
                );
            }
        }

        // Update list timestamp
        $this->saveListForUser($list, $user);

        return ['listId' => $list->getId()];
    }

    /**
     * Add/update a resource in the user's account.
     *
     * @param UserEntityInterface|int     $userOrId        The user entity or ID saving the favorites
     * @param ResourceEntityInterface|int $resourceOrId    The resource entity or ID to add/update
     * @param UserListEntityInterface|int $listOrId        The list entity or ID to store the resource in.
     * @param array                       $tagArray        An array of tags to associate with the resource.
     * @param string                      $notes           User notes about the resource.
     * @param bool                        $replaceExisting Whether to replace all existing tags (true) or
     * append to the existing list (false).
     *
     * @return void
     */
    public function saveResourceToFavorites(
        UserEntityInterface|int $userOrId,
        ResourceEntityInterface|int $resourceOrId,
        VuFindUserListEntityInterface|int $listOrId,
        array $tagArray,
        string $notes,
        bool $replaceExisting = true
    ): void {
        parent::saveResourceToFavorites($userOrId, $resourceOrId, $listOrId, $tagArray, $notes, $replaceExisting);
        $user = $userOrId instanceof UserEntityInterface ? $userOrId : $this->userService->getUserById($userOrId);
        $list = $listOrId instanceof UserListEntityInterface
            ? $listOrId
            : $this->userListService->getUserListById($listOrId);
        // Update list timestamp
        $this->saveListForUser($list, $user);
    }

    /**
     * Update resource custom order.
     *
     * @param UserEntityInterface|int     $userOrId     The user entity or ID saving the favorites.
     * @param ResourceEntityInterface|int $resourceOrId The resource entity or ID to update.
     * @param UserListEntityInterface|int $listOrId     The list entity or ID to update.
     * @param int                         $order        Custom order.
     *
     * @return void
     */
    public function updateResourceCustomOrder(
        UserEntityInterface|int $userOrId,
        ResourceEntityInterface|int $resourceOrId,
        UserListEntityInterface|int $listOrId,
        int $order
    ): void {
        assert($this->userResourceService instanceof UserResourceService);
        $this->userResourceService->createOrUpdateLinkWithOrder($resourceOrId, $userOrId, $listOrId, null, $order);
        $user = $userOrId instanceof UserEntityInterface ? $userOrId : $this->userService->getUserById($userOrId);
        $list = $listOrId instanceof UserListEntityInterface
            ? $listOrId
            : $this->userListService->getUserListById($listOrId);
        // Update list timestamp
        $this->saveListForUser($list, $user);
    }

    /**
     * Given an array of item ids, remove them from the specified list.
     *
     * @param UserListEntityInterface $list   List being updated
     * @param ?UserEntityInterface    $user   Logged-in user (null if none)
     * @param string[]                $ids    IDs to remove from the list
     * @param string                  $source Type of resource identified by IDs
     *
     * @return void
     */
    public function removeListResourcesById(
        VuFindUserListEntityInterface $list,
        ?UserEntityInterface $user,
        array $ids,
        string $source = DEFAULT_SEARCH_BACKEND
    ): void {
        parent::removeListResourcesById($list, $user, $ids, $source);
        // Update list timestamp
        $this->saveListForUser($list, $user);
    }

    /**
     * Create a new list object for the specified user or modify existing list with given title.
     * Update list details and save the list object.
     *
     * @param ?UserEntityInterface $user        Logged in user (null if logged out)
     * @param string               $title       List title
     * @param string               $description List description
     * @param bool                 $public      List publicity
     *
     * @return UserListEntityInterface
     * @throws LoginRequiredException
     */
    public function createAndPersistListForUserWithDetails(
        ?UserEntityInterface $user,
        string $title,
        string $description,
        bool $public
    ): UserListEntityInterface {
        assert($this->userListService instanceof UserListService);
        if (!($list = $this->userListService->getListByTitle($user, $title))) {
            $list = parent::createListForUser($user);
            $list->setTitle($title);
        }
        $list->setDescription($description);
        $list->setPublic($public);
        $this->saveListForUser($list, $user);
        return $list;
    }

    /**
     * Save list custom order.
     *
     * @param UserEntityInterface $user        User id
     * @param int                 $listId      List id
     * @param array               $orderedList Ordered List of Resources
     *
     * @return void
     */
    public function saveCustomFavoriteOrder(UserEntityInterface $user, int $listId, array $orderedList): void
    {
        assert($this->userListService instanceof UserListService);
        $this->userListService->saveCustomFavoriteOrder($user, $listId, $orderedList);
        // Update list timestamp
        $this->saveListForUser($this->userListService->getUserListById($listId), $user);
    }

    /**
     * Add a tag to a list.
     *
     * @param string                  $tagText The tag to save.
     * @param UserListEntityInterface $list    The list being tagged.
     * @param UserEntityInterface     $user    The user posting the tag.
     *
     * @return void
     */
    public function addListTag(string $tagText, VuFindUserListEntityInterface $list, UserEntityInterface $user): void
    {
        parent::addListTag($tagText, $list, $user);
        // Update list timestamp
        $this->saveListForUser($list, $user);
    }

    /**
     * Saves the provided list to the database and remembers it in the session if it is valid;
     * throws an exception otherwise.
     *
     * Finna: updates list's finnaUpdated timestamp as well.
     *
     * @param UserListEntityInterface $list List to save
     * @param ?UserEntityInterface    $user Logged-in user (null if none)
     *
     * @return void
     */
    public function saveListForUser(VuFindUserListEntityInterface $list, ?UserEntityInterface $user): void
    {
        $list->setFinnaUpdated(new DateTime());
        parent::saveListForUser($list, $user);
    }
}
