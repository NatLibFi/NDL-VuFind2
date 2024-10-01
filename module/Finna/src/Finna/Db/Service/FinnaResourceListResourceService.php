<?php

/**
 * Finna resource list resource service.
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
 * @package  Db_Service
 * @author   Juha Luoma <juha.luoma@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development Wiki
 */

namespace Finna\Db\Service;

use Exception;
use Finna\Db\Entity\FinnaResourceListEntityInterface;
use Finna\Db\Entity\FinnaResourceListResourceEntityInterface;
use Finna\Db\Table\FinnaResourceListResource;
use Finna\Db\Table\Resource;
use VuFind\Db\Entity\ResourceEntityInterface;
use VuFind\Db\Entity\UserEntityInterface;
use VuFind\Db\Entity\UserListEntityInterface;
use VuFind\Db\Service\AbstractDbService;
use VuFind\Db\Service\DbServiceAwareInterface;
use VuFind\Db\Service\DbServiceAwareTrait;
use VuFind\Db\Service\ResourceServiceInterface;
use VuFind\Db\Service\UserServiceInterface;
use VuFind\Db\Table\DbTableAwareInterface;
use VuFind\Db\Table\DbTableAwareTrait;

use function is_int;

/**
 * Finna resource list resource service.
 *
 * @category VuFind
 * @package  Db_Service
 * @author   Juha Luoma <juha.luoma@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development Wiki
 */
class FinnaResourceListResourceService extends AbstractDbService implements
    DbTableAwareInterface,
    DbServiceAwareInterface,
    FinnaResourceListResourceServiceInterface
{
    use DbServiceAwareTrait;
    use DbTableAwareTrait;

    /**
     * Create user/resource/list link if one does not exist; update notes if one does.
     *
     * @param ResourceEntityInterface|int $resourceOrId Entity or ID of resource to link up
     * @param UserEntityInterface|int     $userOrId     Entity or ID of user creating link
     * @param UserListEntityInterface|int $listOrId     Entity or ID of list to link up
     * @param string                      $notes        Notes to associate with link
     *
     * @return UserResource|false
     */
    public function createOrUpdateLink(
        ResourceEntityInterface|int $resourceOrId,
        UserEntityInterface|int $userOrId,
        FinnaResourceListEntityInterface|int $listOrId,
        string $notes = ''
    ): FinnaResourceListResourceEntityInterface {
        $resource = $resourceOrId instanceof ResourceEntityInterface
            ? $resourceOrId : $this->getDbService(ResourceServiceInterface::class)->getResourceById($resourceOrId);
        if (!$resource) {
            throw new Exception("Cannot retrieve resource $resourceOrId");
        }
        $list = $listOrId instanceof FinnaResourceListEntityInterface
            ? $listOrId
            : $this->getDbService(FinnaResourceListServiceInterface::class)->getResourceListById($listOrId);
        if (!$list) {
            throw new Exception("Cannot retrieve list $listOrId");
        }
        $user = $userOrId instanceof UserEntityInterface
            ? $userOrId : $this->getDbService(UserServiceInterface::class)->getUserById($userOrId);
        if (!$user) {
            throw new Exception("Cannot retrieve user $userOrId");
        }
        $params = [
            'resource_id' => $resource->getId(),
            'list_id' => $list->getId(),
        ];
        if (!($result = $this->getDbTable(FinnaResourceListResource::class)->select($params)->current())) {
            $result = $this->createEntity()
                ->setUser($user)
                ->setResource($resource)
                ->setNotes($notes)
                ->setList($list);
        }
        // Update the notes:
        $result->setNotes($notes);
        $this->persistEntity($result);
        return $result;
    }

    /**
     * Unlink rows for the specified resource.
     *
     * @param int|int[]|null              $resourceId ID (or array of IDs) of resource(s) to unlink (null for ALL
     * matching resources)
     * @param UserEntityInterface|int     $userOrId   ID or entity representing user removing links
     * @param UserListEntityInterface|int $listOrId   ID or entity representing list to unlink (null for ALL
     * matching lists)
     *
     * @return void
     */
    public function unlinkResources(
        int|array|null $resourceId,
        UserEntityInterface|int $userOrId,
        FinnaResourceListEntityInterface|int|null $listOrId = null
    ): void {
        // Build the where clause to figure out which rows to remove:
        $listId = is_int($listOrId) ? $listOrId : $listOrId?->getId();
        $userId = is_int($userOrId) ? $userOrId : $userOrId->getId();
        $callback = function ($select) use ($resourceId, $userId, $listId) {
            $select->where->equalTo('user_id', $userId);
            if (null !== $resourceId) {
                $select->where->in('resource_id', (array)$resourceId);
            }
            if (null !== $listId) {
                $select->where->equalTo('list_id', $listId);
            }
        };

        // Delete the rows:
        $this->getDbTable(FinnaResourceListResource::class)->delete($callback);
    }

    /**
     * Create a UserResource entity object.
     *
     * @return FinnaResourceListResourceEntityInterface
     */
    public function createEntity(): FinnaResourceListResourceEntityInterface
    {
        return $this->getDbTable(FinnaResourceListResource::class)->createRow();
    }

    /**
     * Change all matching rows to use the new resource ID instead of the old one (called when an ID changes).
     *
     * @param int $old Original resource ID
     * @param int $new New resource ID
     *
     * @return void
     */
    public function changeResourceId(int $old, int $new): void
    {
        $this->getDbTable(FinnaResourceListResource::class)->update(['resource_id' => $new], ['resource_id' => $old]);
    }

    /**
     * Get resources for a reservation list
     *
     * @param UserEntityInterface                   $user   User entity
     * @param FinnaResourceListEntityInterface|null $list   List entity
     * @param string|null                           $sort   Sort order
     * @param int                                   $offset Offset
     * @param int|null                              $limit  Limit
     *
     * @return array
     */
    public function getResourcesForList(
        UserEntityInterface $user,
        ?FinnaResourceListEntityInterface $list = null,
        ?string $sort = null,
        int $offset = 0,
        int $limit = null
    ): array {
        return iterator_to_array(
            $this->getDbTable(Resource::class)->getReservationResources(
                $user?->getId() ?? null,
                $list?->getId() ?? null,
                $sort,
                $offset,
                $limit
            )
        );
    }

    /**
     * Deduplicate rows (sometimes necessary after merging foreign key IDs).
     *
     * @return void
     */
    public function deduplicate(): void
    {
        $this->getDbTable(FinnaResourceListResource::class)->deduplicate();
    }
}
