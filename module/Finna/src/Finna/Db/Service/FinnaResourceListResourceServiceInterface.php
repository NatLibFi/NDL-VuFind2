<?php

/**
 * Finna resource list resource service interface.
 *
 * PHP version 8
 *
 * Copyright (C) The National Library of Finland 2024.
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2,
 * as published by the Free Software Foundation.
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301  USA
 *
 * @category VuFind
 * @package  FinnaResourceList
 * @author   Juha Luoma <juha.luoma@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development Wiki
 */

namespace Finna\Db\Service;

use Finna\Db\Entity\FinnaResourceListEntityInterface;
use Finna\Db\Entity\FinnaResourceListResourceEntityInterface;
use VuFind\Db\Entity\ResourceEntityInterface;
use VuFind\Db\Entity\UserEntityInterface;
use VuFind\Db\Entity\UserListEntityInterface;
use VuFind\Db\Entity\UserResourceEntityInterface;
use VuFind\Db\Service\DbServiceInterface;

/**
 * Finna resource list resource service interface.
 *
 * @category VuFind
 * @package  FinnaResourceList
 * @author   Juha Luoma <juha.luoma@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development Wiki
 */
interface FinnaResourceListResourceServiceInterface extends DbServiceInterface
{
    /**
     * Get information saved in a user's favorites for a particular record.
     *
     * @param string                           $recordId ID of record being checked.
     * @param string                           $source   Source of record to look up
     * @param UserListEntityInterface|int|null $listOrId Optional list entity or ID
     * (to limit results to a particular list).
     * @param UserEntityInterface|int|null     $userOrId Optional user entity or ID
     * (to limit results to a particular user).
     *
     * @return UserResourceEntityInterface[]
     */
    public function getFavoritesForRecord(
        string $recordId,
        string $source = DEFAULT_SEARCH_BACKEND,
        FinnaResourceListEntityInterface|int|null $listOrId = null,
        UserEntityInterface|int|null $userOrId = null
    ): array;

    /**
     * Get statistics on use of UserResource.
     *
     * @return array
     */
    public function getStatistics(): array;

    /**
     * Create user/resource/list link if one does not exist; update notes if one does.
     *
     * @param ResourceEntityInterface|int          $resourceOrId Entity or ID of resource to link up
     * @param UserEntityInterface|int              $userOrId     Entity or ID of user creating link
     * @param FinnaResourceListEntityInterface|int $listOrId     Entity or ID of list to link up
     * @param string                               $notes        Notes to associate with link
     *
     * @return UserResource|false
     */
    public function createOrUpdateLink(
        ResourceEntityInterface|int $resourceOrId,
        UserEntityInterface|int $userOrId,
        FinnaResourceListEntityInterface|int $listOrId,
        string $notes = ''
    ): FinnaResourceListResourceEntityInterface;

    /**
     * Unlink rows for the specified resource.
     *
     * @param int|int[]|null                       $resourceId ID (or array of IDs) of resource(s) to unlink
     *                                                         (null for ALL matching resources)
     * @param UserEntityInterface|int              $userOrId   ID or entity representing user removing links
     * @param FinnaResourceListEntityInterface|int $listOrId   ID or entity representing list to unlink
     *                                                         (null for ALL matching lists)
     *
     * @return void
     */
    public function unlinkFavorites(
        int|array|null $resourceId,
        UserEntityInterface|int $userOrId,
        FinnaResourceListEntityInterface|int|null $listOrId = null
    ): void;

    /**
     * Create a UserResource entity object.
     *
     * @return FinnaResourceListResourceEntityInterface
     */
    public function createEntity(): FinnaResourceListResourceEntityInterface;

    /**
     * Change all matching rows to use the new resource ID instead of the old one (called when an ID changes).
     *
     * @param int $old Original resource ID
     * @param int $new New resource ID
     *
     * @return void
     */
    public function changeResourceId(int $old, int $new): void;

    /**
     * Deduplicate rows (sometimes necessary after merging foreign key IDs).
     *
     * @return void
     */
    public function deduplicate(): void;

    /**
     * Get resources for a reservation list
     *
     * @param UserEntityInterface              $user   User entity
     * @param FinnaResourceListEntityInterface $list   List entity
     * @param string|null                      $sort   Sort order
     * @param int                              $offset Offset
     * @param int|null                         $limit  Limit
     *
     * @return array
     */
    public function getResourcesForList(
        UserEntityInterface $user,
        ?FinnaResourceListEntityInterface $list = null,
        ?string $sort = null,
        int $offset = 0,
        int $limit = null
    ): array;
}
