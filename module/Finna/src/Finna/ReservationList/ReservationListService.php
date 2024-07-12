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

use Finna\ReservationList\Handler\PluginManager;
use Laminas\Config\Config;
use VuFind\Db\Row\User;
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
     * Reservation list function result cache
     *
     * @var array
     */
    protected array $reservationListCache = [];

    /**
     * Default handler
     *
     * @var \Finna\ReservationList\Handler\HandlerInterface
     */
    protected \Finna\ReservationList\Handler\HandlerInterface $defaultHandler;

    /**
     * Construct.
     *
     * @param PluginManager $pluginManager         Reservation list pluginmanager
     * @param Config        $reservationListConfig Reservation List ini as Config
     */
    public function __construct(
        protected PluginManager $pluginManager,
        protected Config $reservationListConfig
    ) {
        $this->defaultHandler = $pluginManager->get(PluginManager::DEFAULT_HANDLER);
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
        return $this->defaultHandler->hasAuthority($user, $id);
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
        $this->flushCache();
        return $this->defaultHandler->addList(
            $user,
            $title,
            $description,
            $datasource,
            $building
        );
    }

    /**
     * Retrieves reservation lists for a given user.
     * Lists can exist in database or in an api provided service in future.
     *
     * @param User $user User for whom to retrieve the reservation lists.
     *
     * @return array An array of reservation lists.
     */
    public function getListsForUser(User $user): array
    {
        if (isset($this->reservationListCache[__FUNCTION__])) {
            return $this->reservationListCache[__FUNCTION__];
        }
        $lists = $this->defaultHandler->getLists($user);
        $result = [];
        $dateFormatter = function ($date) {
            return $date ? date('d.m.Y', strtotime($date)) : '';
        };
        foreach ($lists as $list) {
            $result[] = [
                'id' => $list['id'],
                'title' => $list['title'],
                'ordered' => $list['ordered'],
                'created' => $list['created'],
                'datasource' => $list['datasource'],
                'handler' => $list['handler'],
                'count' => '',
                'pickup_date' => $list['pickup_date'],
                'ordered_formatted' => $dateFormatter($list['ordered']),
                'pickup_date_formatted' => $dateFormatter($list['pickup_date']),
                'created_formatted' => $dateFormatter($list['created']),
            ];
        }
        return $this->reservationListCache[__FUNCTION__] = $result;
    }

    /**
     * Retrieves reservation lists for a specific datasource.
     * Lists can coexist in database or external api in future.
     *
     * @param User   $user       User for whom to retrieve the reservation lists.
     * @param string $datasource Datasource for which to retrieve the reservation lists.
     *
     * @return array An array of reservation lists for the specified datasource.
     */
    public function getListsForDatasource(User $user, string $datasource): array
    {
        if (isset($this->reservationListCache[__FUNCTION__])) {
            return $this->reservationListCache[__FUNCTION__];
        }
        $lists = $this->getListsForUser($user);
        $result = [];
        foreach ($lists as $list) {
            if ($list['datasource'] !== $datasource) {
                continue;
            }
            $result[] = [
                'id' => $list['id'],
                'title' => $list['title'],
                'datasource' => $list['datasource'],
                'ordered' => $list['ordered'],
            ];
        }
        return $this->reservationListCache[__FUNCTION__] = $result;
    }

    /**
     * Retrieves reservation list for a specific user.
     *
     * @param User $user User for whom to retrieve the reservation list.
     * @param int  $id   ID of the reservation list.
     *
     * @return array
     */
    public function getListForUser(User $user, int $id): array
    {
        $cacheKey = implode('|', [__FUNCTION__, $user->id, $id]);
        if (isset($this->reservationListCache[$cacheKey])) {
            return $this->reservationListCache[$cacheKey];
        }
        $list = $this->defaultHandler->getList($user, $id);
        $dateFormatter = function ($date) {
            return $date ? date('d.m.Y', strtotime($date)) : '';
        };
        $result = [];
        if ($list) {
            $result = [
                'id' => $list['id'],
                'title' => $list['title'],
                'ordered' => $list['ordered'],
                'created' => $list['created'],
                'building' => $list['building'],
                'description' => $list['description'],
                'datasource' => $list['datasource'],
                'pickup_date' => $list['pickup_date'],
                'ordered_formatted' => $dateFormatter($list['ordered']),
                'pickup_date_formatted' => $dateFormatter($list['pickup_date']),
                'created_formatted' => $dateFormatter($list['created']),
            ];
        }
        return $this->reservationListCache[$cacheKey] = $result;
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
        $cacheKey = __FUNCTION__ . '|' . $recordId . '|' . $source;
        if (isset($this->reservationListCache[$cacheKey])) {
            return $this->reservationListCache[$cacheKey];
        }
        $lists = $this->defaultHandler->getListsContaining($user, $recordId, $source);
        $result = [];
        foreach ($lists as $list) {
            $result[] = [
                'id' => $list->id,
                'title' => $list->title,
                'ordered' => $list->ordered,
            ];
        }
        return $this->reservationListCache[$cacheKey] = $result;
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
        $cacheKey = __FUNCTION__ . '|' . $recordId . '|' . $source . '|' . $datasource;
        if (isset($this->reservationListCache[$cacheKey])) {
            return $this->reservationListCache[$cacheKey];
        }
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
        return $this->reservationListCache[$cacheKey] = $result;
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
        $this->defaultHandler->addItem(
            $user,
            $listId,
            $recordId,
            $notes,
            $source
        );
        // Clear cache after changes
        $this->flushCache();
        return true;
    }

    /**
     * Set list ordered, returns bool if the setting was successful
     *
     * @param User   $user        User
     * @param string $list_id     Id of the list
     * @param string $pickup_date $pickup_date
     *
     * @return bool
     */
    public function setOrdered($user, $list_id, $pickup_date)
    {
        if (!$this->userHasAuthority($user, $list_id)) {
            throw new ListPermissionException('list_access_denied');
        }
        // Clear cache after changes
        $this->flushCache();
        return $this->defaultHandler->orderList($user, $list_id, $pickup_date);
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
        $result = $this->defaultHandler->deleteList($user, $list_id);
        // Clear cache after changes
        $this->flushCache();
        return $result;
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
    /* public function deleteItems($ids, $listId, $user)
     {
         if (!$this->userHasAuthority($user, $listId)) {
             throw new ListPermissionException('list_access_denied');
         }
         $sorted = [];
         foreach ($ids as $current) {
             [$source, $id] = explode('|', $current, 2);
             if (!isset($sorted[$source])) {
                 $sorted[$source] = [];
             }
             $sorted[$source][] = $id;
         }

          @var \Finna\Db\Table\ReservationListResource
         $reservationListResource = $this->reservationList->getDbTable(\Finna\Db\Table\ReservationListResource::class);
         foreach ($sorted as $source => $ids) {
             $reservationListResource->destroyLinks($ids, $user->id, $listId);
         }
         $this->flushCache();
     }*/

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
        $cacheKey = __FUNCTION__ . '|' . $listId;
        if (isset($this->reservationListCache[$cacheKey])) {
            return $this->reservationListCache[$cacheKey];
        }
        if (!$this->userHasAuthority($user, $listId)) {
            throw new ListPermissionException('list_access_denied');
        }
        $records = $this->defaultHandler->getItems($user, $listId);
        $result = [];
        foreach ($records as $record) {
            $result[] = [
                'id' => $record['id'],
                'record_id' => $record['record_id'],
                'title' => $record['title'],
            ];
        }
        return $this->reservationListCache[$cacheKey] = $result;
    }

    /**
     * Get records for list as HTML
     *
     * @param User $user    User
     * @param int  $list_id ID of the list
     *
     * @return string
     */
    public function getRecordsForListHTML($user, $list_id): string
    {
        $records = $this->getRecordsForList($user, $list_id);
        $text = '';
        foreach ($records as $record) {
            $text .= $record['title'] . ' (' . $record['record_id'] . ') ' . PHP_EOL;
        }
        return $text;
    }

    /**
     * Flush runtime cache
     *
     * @return void
     */
    protected function flushCache(): void
    {
        $this->reservationListCache = [];
    }
}
