<?php

/**
 * Resource list service
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
 * @package  Db_Service
 * @author   Juha Luoma <juha.luoma@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org   Main Site
 */

namespace Finna\Db\Service;

use Finna\Db\Entity\FinnaResourceListEntityInterface;
use Finna\Db\Table\FinnaResourceListResource;
use VuFind\Db\Entity\ResourceEntityInterface;
use VuFind\Db\Entity\UserEntityInterface;
use VuFind\Db\Service\AbstractDbService;
use VuFind\Db\Service\DbServiceAwareTrait;
use VuFind\Db\Table\DbTableAwareInterface;
use VuFind\Db\Table\DbTableAwareTrait;
use VuFind\Exception\RecordMissing as RecordMissingException;
use VuFind\RecordDriver\DefaultRecord;

use function is_int;

/**
 * Resource list service
 *
 * @category VuFind
 * @package  Db_Service
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
    public function deleteResourceList(FinnaResourceListEntityInterface|int $listOrId): void
    {
        $listId = $listOrId instanceof FinnaResourceListEntityInterface ? $listOrId->getId() : $listOrId;
        $this->getDbTable(\Finna\Db\Table\FinnaResourceList::class)->delete(['id' => $listId]);
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
            throw new RecordMissingException('Cannot load reservation list ' . $id);
        }
        return $result;
    }

    /**
     * Get resource lists for user
     *
     * @param UserEntityInterface $user           User entity object or ID
     * @param string              $listIdentifier Identifier of the list used by institution
     * @param string              $institution    Institution name
     * @param ?string             $listType       List type to retrieve settings for or omit for all
     *
     * @return array
     */
    public function getResourceListsForUser(
        UserEntityInterface $user,
        string $listIdentifier = '',
        string $institution = '',
        string $listType = null
    ): array {
        $callback = function ($select) use ($user, $listIdentifier, $listType, $institution) {
            $select->where->equalTo('user_id', $user->getId());
            if ($listType) {
                $select->where->equalTo('list_type', $listType);
            }
            if ($institution) {
                $select->where->equalTo('institution', $institution);
            }
            if ($listIdentifier) {
                $select->where->equalTo('list_config_identifier', $listIdentifier);
            }
            $select->order('institution');
        };
        return iterator_to_array(
            $this->getDbTable(\Finna\Db\Table\FinnaResourceList::class)->select($callback)
        );
    }

    /**
     * Get lists which does not contain given resource
     *
     * @param UserEntityInterface     $user           User entity object or ID
     * @param ResourceEntityInterface $resource       Resource entity to look for
     * @param string                  $listIdentifier Identifier of the list used by institution
     * @param string                  $institution    Institution name
     * @param ?string                 $listType       List type to retrieve settings for or omit for all
     *
     * @return array
     */
    public function getListsNotContainingResource(
        UserEntityInterface $user,
        ResourceEntityInterface $resource,
        string $listIdentifier = '',
        string $institution = '',
        string $listType = null
    ): array {
        $listsContaining = iterator_to_array(
            $this->getDbTable(FinnaResourceListResource::class)->select(
                ['resource_id' => $resource->getId(), 'user_id' => $user->getId()]
            );
        );
        $listIds = [];
        if ($listsContaining) {
            $listIds = array_map(
                fn ($relation) => $relation->getListId(),
                $listsContaining
            );
        }
        $callback = function ($select) use ($listIdentifier, $user, $institution, $listType, $listIds) {
            $select->where->equalTo('user_id', $user->getId());
            if ($institution) {
                $select->where->equalTo('institution', $institution);
            }
            if ($listIdentifier) {
                $select->where->equalTo('list_config_identifier', $listIdentifier);
            }
            if ($listType) {
                $select->where->equalTo('list_type', $listType);
            }
            if ($listIds) {
                $select->where->notIn('id', $listIds);
            }
        };
        return iterator_to_array(
            $this->getDbTable(\Finna\Db\Table\FinnaResourceList::class)->select($callback)
        );
    }
}
