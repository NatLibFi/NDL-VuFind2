<?php

/**
 * Reservation List Service
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
 * @package  Controller
 * @author   Juha Luoma <juha.luoma@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org   Main Site
 */

namespace Finna\ReservationList;

use Finna\Db\Table\ReservationList;
use Laminas\Db\ResultSet\ResultSetInterface;
use Laminas\Stdlib\Parameters;
use VuFind\Db\Row\User;
use VuFind\Db\Table\Resource as ResourceTable;
use VuFind\Db\Table\UserResource as UserResourceTable;
use VuFind\Record\Cache as RecordCache;
use VuFind\Exception\ListPermission as ListPermissionException;

/**
 * Reservation List Service
 *
 * @category VuFind
 * @package  Controller
 * @author   Juha Luoma <juha.luoma@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development Wiki
 */
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
    ) {
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
    public function addListForUser(
        User $user,
        string $description,
        string $title,
        string $datasource,
        string $building
    ): int {
        $row = $this->reservationList->getNew($user);
        return $row->updateFromRequest($user, new Parameters([
            'description' => $description,
            'title' => $title,
            'datasource' => $datasource,
            'building' => $building,
        ]));
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
            $pickupDateStr = '';
            if ($list->pickup_date) {
                $pickupDateStr = strtotime($list->pickup_date);
                $pickupDateStr = date('d.m.Y', $pickupDateStr);
            }
            $orderedDateStr = '';
            if ($list->ordered) {
                $orderedDateStr = strtotime($list->ordered);
                $orderedDateStr = date('d.m.Y', $orderedDateStr);
            }
            $result[] = [
                'id' => $list->id,
                'title' => $list->title,
                'ordered' => $list->ordered,
                'pickup_date' => $list->pickup_date,
                'ordered_formatted' => $orderedDateStr,
                'pickup_date_formatted' => $pickupDateStr,
            ];
        }
        return $result;
    }

    /**
     * Retrieves reservation lists for a given user.
     *
     * @param User $user User for whom to retrieve the reservation lists.
     *
     * @return ResultSetInterface
     */
    public function getListsForUserAsObjects(User $user): ResultSetInterface
    {
        return $this->reservationList->select(['user_id' => $user->id]);
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
                'title' => $list->title,
                'ordered' => $list->ordered,
            ];
        }
        return $result;
    }

    /**
     * Retrieves reservation lists associated with a specific building for a given user.
     *
     * @param User   $user     The user for whom to retrieve the lists.
     * @param string $building The name of the building.
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
                'title' => $list->title,
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
     * @return array
     */
    public function getListForUser(User $user, $id): array
    {
        $list = $this->reservationList->getExisting($id);
        if (!$list->editAllowed($user)) {
            throw new ListPermissionException('list_access_denied');
        }
        $result = [];
        if ($list) {
            $pickupDateStr = '';
            if ($list->pickup_date) {
                $pickupDateStr = strtotime($list->pickup_date);
                $pickupDateStr = date('d.m.Y', $pickupDateStr);
            }
            $orderedDateStr = '';
            if ($list->ordered) {
                $orderedDateStr = strtotime($list->ordered);
                $orderedDateStr = date('d.m.Y', $orderedDateStr);
            }
            $result = [
                'id' => $list->id,
                'title' => $list->title,
                'ordered' => $list->ordered,
                'datasource' => $list->datasource,
                'building' => $list->building,
                'pickup_date' => $list->pickup_date,
                'ordered_formatted' => $orderedDateStr,
                'pickup_date_formatted' => $pickupDateStr,
            ];
        }
        return $result;
    }

    /**
     * Retrieves the lists containing a specific record for a given user and source.
     *
     * @param User   $user     The user identifier.
     * @param string $recordId The ID of the record.
     * @param string $source   The source of the record.
     *
     * @return array  An array of lists containing the specified record.
     */
    public function getListsContaining(User $user, string $recordId, string $source)
    {
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
            if ($compare['ordered']) {
                continue;
            }
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
        if (!$this->userHasAuthority($user, $listId)) {
            throw new ListPermissionException('list_access_denied');
        }
        $resourceTable = $this->reservationList->getDbTable('Resource');
        $resource = $resourceTable->findResource($recordId, $source);

        /** @var \Finna\Db\Table\ReservationListResource */
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
     * Set list ordered, returns bool if the setting was successful
     *
     * @param User   $user    User
     * @param string $list_id Id of the list
     *
     * @return bool
     */
    public function setOrdered($user, $list_id)
    {
        if (!$this->userHasAuthority($user, $listId)) {
            throw new ListPermissionException('list_access_denied');
        }
        $currentList = $this->reservationList->getExisting($list_id);
        $result = $currentList->setOrdered($user);
        return !!$result;
    }

    /**
     * Delete list from the user, returns bool if the removal was successful
     *
     * @param User   $user    User
     * @param string $list_id Id of the list
     *
     * @return bool
     */
    public function deleteList($user, $list_id)
    {
        if (!$this->userHasAuthority($user, $list_id)) {
            throw new ListPermissionException('list_access_denied');
        }
        $currentList = $this->reservationList->getExisting($list_id);
        $result = $currentList->delete($user);
        return !!$result;
    }

    /**
     * Set pickup date for a reservation list.
     *
     * @param User   $user    User
     * @param string $list_id Id of the list
     * @param string $date    Date to set
     * 
     * @return bool
     */
    public function setPickupDate($user, $list_id, $date)
    {
        if (!$this->userHasAuthority($user, $listId)) {
            throw new ListPermissionException('list_access_denied');
        }
        $currentList = $this->reservationList->getExisting($list_id);
        $result = $currentList->setPickupDate($user, $date);
        return !!$result;
    }

    /**
     * Delete a group of items from a reservation list.
     *
     * @param array $ids    Array of IDs in source|id format.
     * @param int   $listId ID of list to delete from
     * @param User  $user   Logged in user
     *
     * @return void
     */
    public function deleteItems($ids, $listId, $user)
    {
        if (!$this->userHasAuthority($user, $listId)) {
            throw new ListPermissionException('list_access_denied');
        }
        // Sort $ids into useful array:
        $sorted = [];
        foreach ($ids as $current) {
            [$source, $id] = explode('|', $current, 2);
            if (!isset($sorted[$source])) {
                $sorted[$source] = [];
            }
            $sorted[$source][] = $id;
        }

        /** @var \Finna\Db\Table\ReservationListResource */
        $reservationListResource = $this->reservationList->getDbTable(\Finna\Db\Table\ReservationListResource::class);
        foreach ($sorted as $source => $ids) {
            $reservationListResource->destroyLinks($ids, $user->id, $listId);
        }
    }

    /**
     * Get records for a list
     *
     * @param User $user   User
     * @param int  $listId ID of the list
     *
     * @return array
     */
    public function getRecordsForList($user, $listId): array
    {
        if (!$this->userHasAuthority($user, $listId)) {
            throw new ListPermissionException('list_access_denied');
        }
        /** @var \Finna\Db\Table\Resource */
        $resource = $this->reservationList->getDbTable(\Finna\Db\Table\Resource::class);
        $records = $resource->getReservationResources($user->id, $listId);
        $result = [];
        foreach ($records as $record) {
            $result[] = [
                'id' => $record->id,
                'record_id' => $record->record_id,
                'title' => $record->title,
            ];
        }
        return $result;
    }
}
