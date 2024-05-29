<?php

namespace Finna\ReservationList;

use Finna\Db\Table\ReservationList;
use Laminas\Stdlib\Parameters;
use VuFind\Db\Table\Resource as ResourceTable;
use VuFind\Db\Table\UserResource as UserResourceTable;
use VuFind\Record\Cache as RecordCache;
use VuFind\Db\Row\User;

class ReservationListService implements \VuFind\I18n\Translator\TranslatorAwareInterface
{
    use \VuFind\I18n\Translator\TranslatorAwareTrait;

    /**
     * Constructs a new ReservationListService object.
     *
     * @param ReservationList   $reservationList   Reservation list table
     * @param ResourceTable     $resource          Resource database table
     * @param UserResourceTable $userResourceTable UserResource table
     * @param ?RecordCache      $cache             Record cache
     */
    public function __construct(
        protected ReservationList $reservationList,
        protected ResourceTable $resource,
        protected UserResourceTable $userResourceTable,
        protected ?RecordCache $cache = null
    )
    {
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

    /**
     * Adds a reservation list for a user.
     *
     * @param User   $user        User for whom the reservation list is being added.
     * @param string $description Description of the reservation list.
     * @param string $title       Title of the reservation list.
     * @param string $datasource  Data source of the reservation list.
     * @param string $building    Building associated with the reservation list.
     *
     * @return int ID of the newly added reservation list.
     */
    public function addListForUser(User $user, string $description, string $title, string $datasource, string $building): int
    {
        $row = $this->reservationList->getNew($user);
        return $row->updateFromRequest($user, new Parameters([
            'description' => $description,
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

    /**
     * Retrieves reservation lists for a given user.
     *
     * @param User $user User for whom to retrieve the reservation lists.
     *
     * @return array An array of reservation lists.
     */
    public function getListsForUser(User $user): array
    {
        $lists = $this->reservationList->select(['user_id' => $user->id]);
        $result = [];
        foreach ($lists as $list) {
            $result[] = [
                'id' => $list->id,
                'title' => $list->title,
                'ordered' => $list->ordered,
            ];
        }
        return $result;
    }

    /**
     * Retrieves reservation lists for a specific datasource.
     *
     * @param User   $user       User for whom to retrieve the reservation lists.
     * @param string $datasource Datasource for which to retrieve the reservation lists.
     *
     * @return array An array of reservation lists for the specified datasource.
     */
    public function getListsForDatasource(User $user, string $datasource): array
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

    /**
     * Retrieves reservation lists associated with a specific building for a given user.
     *
     * @param  User   $user     The user for whom to retrieve the lists.
     * @param  string $building The name of the building.
     *
     * @return array  An array of reservation lists, each containing 'id' and 'title'.
     */
    public function getListsForBuilding(User $user, string $building): array
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

    /**
     * Retrieves reservation list for a specific user.
     *
     * @param User $user User for whom to retrieve the reservation list.
     * @param int  $id   ID of the reservation list.
     *
     * @return \Laminas\Db\ResultSet\ResultSetInterface;
     */
    public function getListForUser(User $user, $id)
    {
        return $this->reservationList->select(['user_id' => $user->id, 'id' => $id])->current();
    }
    /**
     * Retrieves the lists containing a specific record for a given user and source.
     *
     * @param  User   $user     The user identifier.
     * @param  string $recordId The ID of the record.
     * @param  string $source   The source of the record.
     *
     * @return array  An array of lists containing the specified record.
     */
    public function getListsContaining(User $user, string $recordId, string $source) {
        $lists = $this->reservationList->getListsContainingResource($recordId, $source, $user);
        $result = [];
        foreach ($lists as $list) {
            $result[] = [
                'id' => $list->id,
                'title' => $list->title,
                'ordered' => $list->ordered,
            ];
        }
        return $result;
    }
    /**
     * Retrieves reservation lists without a record.
     *
     * @param User   $user       User object.
     * @param string $recordId   ID of the record.
     * @param string $source     Source of the record.
     * @param string $datasource Datasource of the record.
     *
     * @return array An array of reservations without a record.
     */
    public function getListsWithoutRecord(User $user, string $recordId, string $source, string $datasource): array
    {
        $lists = $this->getListsContaining($user, $recordId, $source);
        $datasourced = $this->getListsForDatasource($user, $datasource);
        $result = [];
        foreach ($datasourced as $compare) {
            foreach ($lists as $list) {
                if ($list['id'] === $compare['id']) {
                    continue 2;
                }

            }
            $result[] = $compare;
        }
        return $result;
    }

    /**
     * Save a record into a reservation list.
     *
     * @param User   $user     User to save to
     * @param string $recordId Id of the record
     * @param string $listId   Id of the desired list
     * @param string $notes    Notes to be added for a reservationlist resource
     * @param string $source   Source of the search backend where the record is obtained from.
     *                         Default is 'solr'
     *
     * @return bool True
     */
    public function addRecordToList(
        User $user,
        string $recordId,
        string $listId,
        string $notes = '',
        string $source = DEFAULT_SEARCH_BACKEND
    ): bool {
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
    public function setOrdered($user, $list_id)
    {
        $currentList = $this->reservationList->getExisting($list_id);
        $result = $currentList->setOrdered($user);
        return !!$result;
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
