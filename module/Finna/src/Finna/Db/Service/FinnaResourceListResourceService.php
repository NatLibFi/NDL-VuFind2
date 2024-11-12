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

use Finna\Db\Entity\FinnaResourceListEntityInterface;
use Finna\Db\Entity\FinnaResourceListResourceEntityInterface;
use Finna\Db\Table\FinnaResourceListResource;
use Finna\Db\Table\Resource;
use Laminas\Db\Sql\Expression;
use Laminas\Db\Sql\Select;
use VuFind\Db\Entity\ResourceEntityInterface;
use VuFind\Db\Entity\UserEntityInterface;
use VuFind\Db\Service\AbstractDbService;
use VuFind\Db\Service\DbServiceAwareInterface;
use VuFind\Db\Service\DbServiceAwareTrait;
use VuFind\Db\Table\DbTableAwareInterface;
use VuFind\Db\Table\DbTableAwareTrait;

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
     * @param ResourceEntityInterface          $resource Entity
     * @param UserEntityInterface              $user     Entity
     * @param FinnaResourceListEntityInterface $list     Entity
     * @param string                           $notes    Notes to associate with link
     *
     * @return FinnaResourceListResourceEntityInterface
     */
    public function createOrUpdateLink(
        ResourceEntityInterface $resource,
        UserEntityInterface $user,
        FinnaResourceListEntityInterface $list,
        string $notes = ''
    ): FinnaResourceListResourceEntityInterface {
        $params = [
            'resource_id' => $resource->getId(),
            'list_id' => $list->getId(),
            'user_id' => $user->getId(),
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
     * @param int|int[]|null                   $resourceId ID (or array of IDs) of resource(s) to unlink (null for ALL
     *                                                     matching resources)
     * @param UserEntityInterface              $user       User entity
     * @param FinnaResourceListEntityInterface $list       List entity
     *
     * @return void
     */
    public function unlinkResources(
        int|array|null $resourceId,
        UserEntityInterface $user,
        FinnaResourceListEntityInterface $list
    ): void {
        // Build the where clause to figure out which rows to remove:
        $callback = function ($select) use ($resourceId, $user, $list) {
            $select->where->equalTo('user_id', $user->getId());
            if (null !== $resourceId) {
                $select->where->in('resource_id', (array)$resourceId);
            }
            $select->where->equalTo('list_id', $list->getId());
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
     * Get resources for a resource list
     *
     * @param UserEntityInterface              $user   User entity
     * @param FinnaResourceListEntityInterface $list   List entity
     * @param string|null                      $sort   Sort order
     * @param int                              $offset Offset
     * @param int                              $limit  Limit
     *
     * @return array
     */
    public function getResourcesForList(
        UserEntityInterface $user,
        FinnaResourceListEntityInterface $list,
        ?string $sort = null,
        int $offset = 0,
        int $limit = -1
    ): array {
        $relations = iterator_to_array(
            $this->getDbTable(FinnaResourceListResource::class)->select(
                [
                    'list_id' => $list->getId(),
                    'user_id' => $user->getId(),
                ]
            )
        );
        $resourceIds = array_map(
            fn ($relation) => $relation->getResourceId(),
            $relations
        );
        if (!$resourceIds) {
            return [];
        }
        $callback = function ($select) use ($sort, $offset, $limit, $resourceIds) {
            $select->where->in('id', $resourceIds);
            $columns = [
                new Expression(
                    'DISTINCT(?)',
                    ['resource.id'],
                    [Expression::TYPE_IDENTIFIER]
                ), Select::SQL_STAR,
            ];
            $select->columns($columns);
            if ($sort) {
                Resource::applySort($select, $sort, 'resource', $columns);
            }
            if ($offset > 0) {
                $select->offset($offset);
            }
            if ($limit > 0) {
                $select->limit($limit);
            }
        };
        return iterator_to_array(
            $this->getDbTable(Resource::class)->select($callback)
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
