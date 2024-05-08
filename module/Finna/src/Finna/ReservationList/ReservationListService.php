<?php

namespace Finna\ReservationList;

use Finna\Db\Table\ReservationList;
use Laminas\Stdlib\Parameters;
use VuFind\Db\Table\Resource as ResourceTable;
use VuFind\Db\Table\UserResource as UserResourceTable;
use VuFind\Record\Cache as RecordCache;

class ReservationListService implements \VuFind\I18n\Translator\TranslatorAwareInterface
{
    use \VuFind\I18n\Translator\TranslatorAwareTrait;

    /**
     * UserResource table
     *
     * @var UserResourceTable
     */
    protected $userResourceTable;

    /**
     * Reservation list table
     *
     * @var ReservationList
     */
    protected $reservationList;

    /**
     * Resource database table
     *
     * @var ResourceTable
     */
    protected $resourceTable;

    /**
     * Record cache
     *
     * @var RecordCache
     */
    protected $recordCache = null;


    public function __construct(
        ReservationList $reservationList,
        ResourceTable $resource,
        RecordCache $cache = null,
        UserResourceTable $userResourceTable
    ) {
        $this->recordCache = $cache;
        $this->reservationList = $reservationList;
        $this->resourceTable = $resource;
        $this->userResourceTable = $userResourceTable;
    }

    /**
     * Checks that if the user has authority over certain reservation list
     *
     * @param User $user User to check
     * @param int  $id   Id of the list
     *
     * @return bool
     */
    public function userHasAuthority($user, $id): bool
    {
        return $this->reservationList->getExisting($id)->editAllowed($user);
    }

    public function addListForUser($user, $desription, $title, $datasource, $building): int
    {
        $row = $this->reservationList->getNew($user);
        $title = $title;
        return $row->updateFromRequest($user, new Parameters([
            'description' => $desription,
            'title' => $title,
            'datasource' => $datasource,
            'building' => $building
        ]));
    }

    public function getListItemCount($user, $list): int
    {
        $resource = $this->getDbTable(\Finna\Db\Table\ReservationListResource::class);
        $result = $resource->getReservationList(
            $this->id,
            $list->id,
            null,
            null
        );
        return count($result);
    }

    public function getListsForUser(\VuFind\Db\Row\User $user): array
    {
        $lists = $this->reservationList->select(['user_id' => $user->id]);
        $result = [];
        foreach ($lists as $list) {
            $result[] = [
                'id' => $list->id,
                'title' => $list->title
            ];
        }
        return $result;
    }

    public function getListsForDatasource(\VuFind\Db\Row\User $user, string $datasource): array
    {
        $lists = $this->reservationList->select(['user_id' => $user->id, 'datasource' => $datasource]);
        $result = [];
        foreach ($lists as $list) {
            $result[] = [
                'id' => $list->id,
                'title' => $list->title
            ];
        }
        return $result;
    }

    public function getListsForBuilding(\VuFind\Db\Row\User $user, string $building): array
    {
        $lists = $this->reservationList->select(['user_id' => $user->id, 'building' => $building]);
        $result = [];
        foreach ($lists as $list) {
            $result[] = [
                'id' => $list->id,
                'title' => $list->title
            ];
        }
        return $result;
    }

    public function getListForUser($user, $id)
    {
        return $this->reservationList->select(['user_id' => $user->id, 'id' => $id])->current();
    }

    /**
     * Save a record into a reservation list.
     *
     * @param User $user User to save to
     * @param string $recordId Id of the record
     * @param string $listId   Id of the desired list
     * @param string $notes    Notes to be added for a reservationlist resource
     * @param string $source   Source of the search backend where the record is obtained from.
     *                         Default is 'solr'
     *
     * @return bool True on success, fail on error
     */
    public function addRecordToList($user, $recordId, $listId, $notes = '', $source = DEFAULT_SEARCH_BACKEND): bool
    {
        $resourceTable = $this->reservationList->getDbTable('Resource');
        $resource = $resourceTable->findResource($recordId, $source);

        $userResourceTable = $this->reservationList->getDbTable(\Finna\Db\Table\ReservationListResource::class);
        $userResourceTable->createOrUpdateLink(
            $resource->id,
            $user->id,
            $listId,
            $notes
        );
        return true;
    }

    /**
     * Delete list from the user, returns bool if the removal was successful
     *
     * @param User   $user    User
     * @param string $list_id Id of the list
     * @return bool
     */
    public function deleteList($user, $list_id)
    {
        $currentList = $this->reservationList->getExisting($list_id);
        $result = $currentList->delete($user);
        return !!$result;
    }
}
