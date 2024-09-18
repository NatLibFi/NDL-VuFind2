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
use VuFind\Db\Service\DbServiceAwareTrait;
use VuFind\Db\Table\DbTableAwareInterface;
use VuFind\Db\Table\DbTableAwareTrait;
use VuFind\Exception\RecordMissing as RecordMissingException;
use VuFind\RecordDriver\DefaultRecord;

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
class FinnaResourceListService extends AbstractDbService implements
    DbTableAwareInterface,
    FinnaResourceListServiceInterface
{
    use DbTableAwareTrait;
    use DbServiceAwareTrait;

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
     * @param string                       $recordOrId Record or ID of record being checked.
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
        $recordId = $recordOrId instanceof DefaultRecord ? $recordOrId->getUniqueID() : $recordOrId;
        return iterator_to_array(
            $this->getDbTable(\Finna\Db\Table\FinnaResourceList::class)->getListsContainingResource(
                $recordId,
                $source,
                is_int($userOrId) ? $userOrId : $userOrId->getId()
            )
        );
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

    /**
     * Retrieve list objects with ids.
     *
     * @param array $ids Array containing ids of lists.
     *
     * @return FinnaResourceListEntityInterface[]
     * @throws RecordMissingException
     */
    public function getResourceListsByIds(array $ids): array
    {
        $callback = function ($select) use ($ids) {
            $select->where->in('id', $ids);
        };
        return iterator_to_array(
            $this->getDbTable(\Finna\Db\Table\FinnaResourceList::class)->select($callback)
        );
    }
}
