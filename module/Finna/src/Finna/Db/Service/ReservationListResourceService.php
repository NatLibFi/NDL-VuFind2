<?php

namespace Finna\Db\Service;

use Exception;
use Finna\Db\Entity\ReservationListEntityInterface;
use Finna\Db\Entity\ReservationListResourceEntityInterface;
use Finna\Db\Table\ReservationListResource;
use VuFind\Db\Entity\ResourceEntityInterface;
use VuFind\Db\Entity\UserEntityInterface;
use VuFind\Db\Entity\UserListEntityInterface;
use VuFind\Db\Entity\UserResourceEntityInterface;
use VuFind\Db\Service\AbstractDbService;
use VuFind\Db\Service\DbServiceAwareInterface;
use VuFind\Db\Service\DbServiceAwareTrait;
use VuFind\Db\Service\ResourceServiceInterface;
use VuFind\Db\Service\UserServiceInterface;
use VuFind\Db\Table\DbTableAwareInterface;
use VuFind\Db\Table\DbTableAwareTrait;

use function is_int;

class ReservationListResourceService extends AbstractDbService implements
    DbTableAwareInterface,
    DbServiceAwareInterface,
    ReservationListResourceServiceInterface
{
    use DbServiceAwareTrait;
    use DbTableAwareTrait;

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
    ): array {
        $listId = is_int($listOrId) ? $listOrId : $listOrId?->getId();
        $userId = is_int($userOrId) ? $userOrId : $userOrId?->getId();
        return iterator_to_array(
            $this->getDbTable(ReservationListResource::class)->getSavedData($recordId, $source, $listId, $userId)
        );
    }

    /**
     * Get statistics on use of UserResource.
     *
     * @return array
     */
    public function getStatistics(): array
    {
        return $this->getDbTable(ReservationListResource::class)->getStatistics();
    }

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
        ReservationListEntityInterface|int $listOrId,
        string $notes = ''
    ): ReservationListResourceEntityInterface {
        $resource = $resourceOrId instanceof ResourceEntityInterface
            ? $resourceOrId : $this->getDbService(ResourceServiceInterface::class)->getResourceById($resourceOrId);
        if (!$resource) {
            throw new Exception("Cannot retrieve resource $resourceOrId");
        }
        $list = $listOrId instanceof UserListEntityInterface
            ? $listOrId : $this->getDbService(ReservationListServiceInterface::class)->getReservationListById($listOrId);
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
        if (!($result = $this->getDbTable(ReservationListResource::class)->select($params)->current())) {
            $result = $this->createEntity()
                ->setResource($resource)
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
    public function unlinkFavorites(
        int|array|null $resourceId,
        UserEntityInterface|int $userOrId,
        ReservationListEntityInterface|int|null $listOrId = null
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
        $this->getDbTable(ReservationListResource::class)->delete($callback);
    }

    /**
     * Create a UserResource entity object.
     *
     * @return ReservationListResourceEntityInterface
     */
    public function createEntity(): ReservationListResourceEntityInterface
    {
        return $this->getDbTable(ReservationListResource::class)->createRow();
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
        $this->getDbTable(ReservationListResource::class)->update(['resource_id' => $new], ['resource_id' => $old]);
    }

    /**
     * Get resources for a reservation list
     *
     * @param ReservationListEntityInterface $list
     *
     * @return array
     */
    public function getResourcesForList(ReservationListEntityInterface $list): array
    {
        return iterator_to_array(
            $this->getDbTable(ReservationListResource::class)->getRecordsForList($list)
        );
    }

    /**
     * Deduplicate rows (sometimes necessary after merging foreign key IDs).
     *
     * @return void
     */
    public function deduplicate(): void
    {
        $this->getDbTable(ReservationListResource::class)->deduplicate();
    }
}
