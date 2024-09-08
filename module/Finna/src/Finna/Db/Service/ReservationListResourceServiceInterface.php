<?php

namespace Finna\Db\Service;

use Finna\Db\Entity\ReservationListEntityInterface;
use VuFind\Db\Entity\ResourceEntityInterface;
use VuFind\Db\Entity\UserEntityInterface;
use VuFind\Db\Entity\UserListEntityInterface;
use VuFind\Db\Entity\UserResourceEntityInterface;
use VuFind\Db\Service\DbServiceInterface;
use Finna\Db\Entity\ReservationListResourceEntityInterface;

interface ReservationListResourceServiceInterface extends DbServiceInterface
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
        ReservationListEntityInterface|int|null $listOrId = null,
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
     * @param ResourceEntityInterface|int $resourceOrId Entity or ID of resource to link up
     * @param UserEntityInterface|int     $userOrId     Entity or ID of user creating link
     * @param ReservationListEntityInterface|int $listOrId     Entity or ID of list to link up
     * @param string                      $notes        Notes to associate with link
     *
     * @return UserResource|false
     */
    public function createOrUpdateLink(
        ResourceEntityInterface|int $resourceOrId,
        UserEntityInterface|int $userOrId,
        ReservationListEntityInterface|int $listOrId,
        string $notes = ''
    ): ReservationListResourceEntityInterface;

    /**
     * Unlink rows for the specified resource.
     *
     * @param int|int[]|null              $resourceId ID (or array of IDs) of resource(s) to unlink (null for ALL
     * matching resources)
     * @param UserEntityInterface|int     $userOrId   ID or entity representing user removing links
     * @param ReservationListEntityInterface|int $listOrId   ID or entity representing list to unlink (null for ALL
     * matching lists)
     *
     * @return void
     */
    public function unlinkFavorites(
        int|array|null $resourceId,
        UserEntityInterface|int $userOrId,
        ReservationListEntityInterface|int|null $listOrId = null
    ): void;

    /**
     * Create a UserResource entity object.
     *
     * @return ReservationListResourceEntityInterface
     */
    public function createEntity(): ReservationListResourceEntityInterface;

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
     * @param ReservationListEntityInterface $list
     *
     * @return array
     */
    public function getResourcesForList(ReservationListEntityInterface $list): array;
}
