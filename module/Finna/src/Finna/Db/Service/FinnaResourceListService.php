<?php

/**
 * Reservation List Service
 *
 * PHP version 8.1
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
 * @package  FinnaResourceList
 * @author   Juha Luoma <juha.luoma@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org   Main Site
 */

namespace Finna\Db\Service;

use Finna\Db\Entity\FinnaResourceListEntityInterface;
use Laminas\Db\Sql\Expression;
use Laminas\Db\Sql\ExpressionInterface;
use Laminas\Db\Sql\Select;
use VuFind\Db\Entity\UserEntityInterface;
use VuFind\Db\Service\AbstractDbService;
use VuFind\Db\Table\DbTableAwareInterface;
use VuFind\Db\Table\DbTableAwareTrait;
use VuFind\Exception\RecordMissing as RecordMissingException;

use function is_int;

/**
 * Reservation List Service
 *
 * @category VuFind
 * @package  FinnaResourceList
 * @author   Juha Luoma <juha.luoma@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development Wiki
 */
class FinnaResourceListService extends AbstractDbService implements DbTableAwareInterface, FinnaResourceListServiceInterface
{
    use DbTableAwareTrait;

    /**
     * Create a FinnaResourceList entity object.
     *
     * @return FinnaResourceListEntityInterface
     */
    public function createEntity(): FinnaResourceListEntityInterface
    {
        return $this->getDbTable(\Finna\Db\Table\FinnaResourceList::class)->createRow();
    }

    /**
     * Delete a user list entity.
     *
     * @param FinnaResourceListEntityInterface|int $listOrId List entity object or ID to delete
     *
     * @return void
     */
    public function deleteFinnaResourceList(FinnaResourceListEntityInterface|int $listOrId): void
    {
        $listId = $listOrId instanceof FinnaResourceListEntityInterface ? $listOrId->getId() : $listOrId;
        $this->getDbTable(\Finna\Db\Table\FinnaResourceList::class)->delete(['id' => $listId]);
    }

    /**
     * Retrieve a list object.
     *
     * @param int $id Numeric ID for existing list.
     *
     * @return FinnaResourceListEntityInterface
     * @throws RecordMissingException
     */
    public function getFinnaResourceListById(int $id): FinnaResourceListEntityInterface
    {
        $result = $this->getDbTable(\Finna\Db\Table\FinnaResourceList::class)->select(['id' => $id])->current();
        if (empty($result)) {
            throw new RecordMissingException('Cannot load reservation list ' . $id);
        }
        return $result;
    }

    /**
     * Get public lists.
     *
     * @param array $includeFilter List of list ids or entities to include in result.
     * @param array $excludeFilter List of list ids or entities to exclude from result.
     *
     * @return FinnaResourceListEntityInterface[]
     */
    public function getPublicLists(array $includeFilter = [], array $excludeFilter = []): array
    {
        return [];
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
    public function getFinnaResourceListsAndCountsByUser(UserEntityInterface|int $userOrId): array
    {
        $userId = $userOrId instanceof UserEntityInterface ? $userOrId->getId() : $userOrId;
        $callback = function (Select $select) use ($userId) {
            $select->columns(
                [
                    Select::SQL_STAR,
                    'cnt' => new Expression(
                        'COUNT(DISTINCT(?))',
                        ['ur.resource_id'],
                        [ExpressionInterface::TYPE_IDENTIFIER]
                    ),
                ]
            );
            $select->join(
                ['ur' => 'finna_resource_list_resource'],
                'finna_resource_list.id = ur.list_id',
                [],
                $select::JOIN_LEFT
            );
            $select->where->equalTo('finna_resource_list.user_id', $userId);
            $select->group(
                [
                    'finna_resource_list.id', 'finna_resource_list.user_id', 'title', 'description',
                    'created',
                ]
            );
            $select->order(['title']);
        };

        $result = [];
        foreach ($this->getDbTable(\Finna\Db\Table\FinnaResourceList::class)->select($callback) as $row) {
            $result[] = ['list_entity' => $row, 'count' => $row->cnt];
        }
        return $result;
    }

    /**
     * Get lists associated with a particular tag and/or list of IDs. If IDs and
     * tags are both provided, only the intersection of matches will be returned.
     *
     * @param string|string[]|null $tag               Tag or tags to match (by text, not ID; null for all)
     * @param int|int[]|null       $listId            List ID or IDs to match (null for all)
     * @param bool                 $publicOnly        Whether to return only public lists
     * @param bool                 $andTags           Use AND operator when filtering by tag.
     * @param bool                 $caseSensitiveTags Should we treat tags case-sensitively?
     *
     * @return FinnaResourceListEntityInterface[]
     */
    public function getFinnaResourceListsByTagAndId(
        string|array|null $tag = null,
        int|array|null $listId = null,
        bool $publicOnly = true,
        bool $andTags = true,
        bool $caseSensitiveTags = false
    ): array {
        return [];
    }

    /**
     * Get list objects belonging to the specified user.
     *
     * @param UserEntityInterface|int $userOrId User entity object or ID
     *
     * @return FinnaResourceListEntityInterface[]
     */
    public function getResourceListsByUser(UserEntityInterface|int $userOrId): array
    {
        $userId = $userOrId instanceof UserEntityInterface ? $userOrId->getId() : $userOrId;
        $callback = function ($select) use ($userId) {
            $select->where->equalTo('user_id', $userId);
            $select->order(['title']);
        };
        return iterator_to_array($this->getDbTable(\Finna\Db\Table\FinnaResourceList::class)->select($callback));
    }

    /**
     * Get lists containing a specific record.
     *
     * @param string                       $recordId ID of record being checked.
     * @param string                       $source   Source of record to look up
     * @param UserEntityInterface|int|null $userOrId Optional user ID or entity object (to limit results
     * to a particular user).
     *
     * @return FinnaResourceListEntityInterface[]
     */
    public function getListsContainingRecord(
        string $recordId,
        string $source = DEFAULT_SEARCH_BACKEND,
        UserEntityInterface|int|null $userOrId = null
    ): array {
        return iterator_to_array(
            $this->getDbTable(\Finna\Db\Table\FinnaResourceList::class)->getListsContainingResource(
                $recordId,
                $source,
                is_int($userOrId) ? $userOrId : $userOrId->getId()
            )
        );
    }

    /**
     * Get lists containing a specific record.
     *
     * @param string                       $recordId ID of record being checked.
     * @param string                       $source   Source of record to look up
     * @param UserEntityInterface|int|null $userOrId Optional user ID or entity object (to limit results
     * to a particular user).
     *
     * @return FinnaResourceListEntityInterface[]
     */
    public function getListsNotContainingRecord(
        string $recordId,
        string $source = DEFAULT_SEARCH_BACKEND,
        UserEntityInterface|int|null $userOrId = null
    ): array {
        $listsContaining = $this->getListsContainingRecord($recordId, $source, $userOrId);
        $allLists = $this->getResourceListsByUser($userOrId);
        $ids = array_map(
            function($obj) {
                return $obj->getId();
            },
            $listsContaining
        );
        var_dump(count($listsContaining));
        var_dump(count($allLists));
        return array_filter($allLists, function($obj) use ($ids) {
            return !in_array($obj->getId(), $ids);
        });
    }

    /**
     * Is the provided user allowed to edit the provided list?
     *
     * @param ?UserEntityInterface    $user Logged-in user (null if none)
     * @param UserListEntityInterface $list List to check
     *
     * @return bool
     */
    public function userCanEditList(?UserEntityInterface $user, FinnaResourceListEntityInterface $list): bool
    {
        return $this->resourceList->userCanEditList($user, $list);
    }

        /**
     * Retrieve a list object.
     *
     * @param int $id Numeric ID for existing list.
     *
     * @return FinnaResourceListEntityInterface
     * @throws RecordMissingException
     */
    public function getResourceListById(int $id): FinnaResourceListEntityInterface
    {
        $result = $this->getDbTable(\Finna\Db\Table\FinnaResourceList::class)->select(['id' => $id])->current();
        if (empty($result)) {
            throw new RecordMissingException('Cannot load list ' . $id);
        }
        return $result;
    }
}
