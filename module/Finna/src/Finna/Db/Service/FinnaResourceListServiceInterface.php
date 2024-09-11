<?php

namespace Finna\Db\Service;

use Finna\Db\Entity\FinnaResourceListEntityInterface;
use VuFind\Db\Entity\UserEntityInterface;
use VuFind\Db\Service\DbServiceInterface;

interface FinnaResourceListServiceInterface extends DbServiceInterface
{
    /**
     * Create a FinnaResourceList entity object.
     *
     * @return FinnaResourceListEntityInterface
     */
    public function createEntity(): FinnaResourceListEntityInterface;

    /**
     * Delete a user list entity.
     *
     * @param FinnaResourceListEntityInterface|int $listOrId List entity object or ID to delete
     *
     * @return void
     */
    public function deleteFinnaResourceList(FinnaResourceListEntityInterface|int $listOrId): void;

    /**
     * Retrieve a list object.
     *
     * @param int $id Numeric ID for existing list.
     *
     * @return FinnaResourceListEntityInterface
     * @throws RecordMissingException
     */
    public function getFinnaResourceListById(int $id): FinnaResourceListEntityInterface;

    /**
     * Get public lists.
     *
     * @param array $includeFilter List of list ids or entities to include in result.
     * @param array $excludeFilter List of list ids or entities to exclude from result.
     *
     * @return FinnaResourceListEntityInterface[]
     */
    public function getPublicLists(array $includeFilter = [], array $excludeFilter = []): array;

    /**
     * Get lists belonging to the user and their count. Returns an array of arrays with
     * list_entity and count keys.
     *
     * @param UserEntityInterface|int $userOrId User entity object or ID
     *
     * @return array
     * @throws Exception
     */
    public function getFinnaResourceListsAndCountsByUser(UserEntityInterface|int $userOrId): array;

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
    ): array;

    /**
     * Get list objects belonging to the specified user.
     *
     * @param UserEntityInterface|int $userOrId User entity object or ID
     *
     * @return FinnaResourceListEntityInterface[]
     */
    public function getResourceListsByUser(UserEntityInterface|int $userOrId): array;

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
    ): array;

            /**
     * Retrieve a list object.
     *
     * @param int $id Numeric ID for existing list.
     *
     * @return FinnaResourceListEntityInterface
     * @throws RecordMissingException
     */
    public function getResourceListById(int $id): FinnaResourceListEntityInterface;

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
    ): array;
}
