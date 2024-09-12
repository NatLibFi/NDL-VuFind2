<?php

/**
 * Database service for UserList.
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
 * @package  Database
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:database_gateways Wiki
 */

namespace Finna\Db\Service;

use VuFind\Db\Entity\UserEntityInterface;
use VuFind\Db\Entity\UserListEntityInterface;

use function is_int;

/**
 * Database service for UserList.
 *
 * @category VuFind
 * @package  Database
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:database_gateways Wiki
 */
class UserListService extends \VuFind\Db\Service\UserListService implements FinnaUserListServiceInterface
{
    /**
     * Check if custom favorite order is used in a list
     *
     * @param UserListEntityInterface|int $listOrId List entity or ID.
     *
     * @return bool
     */
    public function isCustomOrderAvailable(UserListEntityInterface|int $listOrId)
    {
        $listId = is_int($listOrId) ? $listOrId : $listOrId?->getId();
        $callback = function ($select) use ($listId) {
            $select->where->equalTo('list_id', $listId);
            $select->join(
                ['r' => 'resource'],
                'user_resource.resource_id = r.id',
                ['record_id']
            );
            $select->where->isNotNull('finna_custom_order_index');
        };
        return $this->getDbTable('UserResource')->select($callback)->count() > 0;
    }

    /**
     * Get next available custom order index
     *
     * @param UserListEntityInterface|int $listOrId List entity or ID.
     *
     * @return int Next available index or zero if custom order is not used or list is empty
     */
    public function getNextAvailableCustomOrderIndex(UserListEntityInterface|int $listOrId)
    {
        $listId = is_int($listOrId) ? $listOrId : $listOrId?->getId();
        $callback = function ($select) use ($listId) {
            $select->where->equalTo('list_id', $listId);
            $select->where->isNotNull('finna_custom_order_index');
            $select->order('finna_custom_order_index DESC');
        };
        $result = $this->getDbTable('UserResource')->select($callback);
        if ($result->count() > 0) {
            return $result->current()->finna_custom_order_index + 1;
        }
        return 0;
    }

    /**
     * Retrieve user's list object by title.
     *
     * @param UserEntityInterface|int $userOrId User entity or ID.
     * @param string                  $title    Title of the list to retrieve
     *
     * @return ?UserListEntityInterface
     */
    public function getListByTitle(UserEntityInterface|int $userOrId, string $title): ?UserListEntityInterface
    {
        $userId = $userOrId instanceof UserEntityInterface ? $userOrId->getId() : $userOrId;
        $result = $this->getDbTable('UserList')->getByTitle($userId, $title);
        return false !== $result ? $result : null;
    }

    /**
     * Get lists belonging to the user and their count. Returns an array of arrays with
     * list_entity and count keys.
     *
     * @param UserEntityInterface|int $userOrId User entity object or ID
     *
     * @return array
     * @throws Exception
     */
    public function getUserListsAndCountsByUser(UserEntityInterface|int $userOrId): array
    {
        $lists = parent::getUserListsAndCountsByUser($userOrId);

        // Sort lists by id
        $listsSorted = [];
        foreach ($lists as $l) {
            $listsSorted[$l['list_entity']->getId()] = $l;
        }
        ksort($listsSorted);

        return array_values($listsSorted);
    }

    /**
     * Update custom favorite list order
     *
     * @param UserEntityInterface $user        User id
     * @param int                 $listId      List id
     * @param array               $orderedList Ordered List of Resources
     *
     * @return void
     */
    public function saveCustomFavoriteOrder(UserEntityInterface $user, int $listId, array $orderedList): void
    {
        $this->getDbTable('UserResource')->saveCustomFavoriteOrder($user->getId(), $listId, $orderedList);
    }

    /**
     * Retrieve protected lists.
     *
     * @return UserListEntityInterface[]
     */
    public function getProtectedLists(): array
    {
        return iterator_to_array($this->getDbTable('UserList')->select(['finna_protected' => 1]));
    }
}
