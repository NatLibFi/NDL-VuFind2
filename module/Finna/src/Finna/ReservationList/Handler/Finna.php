<?php

/**
 * Class Finna
 *
 * PHP version 8.1
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
 * @package  ReservationList
 * @author   Juha Luoma <juha.luoma@helsinki.fi>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development Wiki
 */

declare(strict_types=1);

namespace Finna\ReservationList\Handler;

use Finna\Db\Table\ReservationList;
use Laminas\Stdlib\Parameters;
use VuFind\Db\Row\User;
use VuFind\Db\Table\Resource as ResourceTable;
use VuFind\Db\Table\UserResource as UserResourceTable;

/**
 * Class Finna. Controls the data of forms in Finna.
 *
 * @category VuFind
 * @package  ReservationList
 * @author   Juha Luoma <juha.luoma@helsinki.fi>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development Wiki
 */
class Finna implements HandlerInterface, \Laminas\Log\LoggerAwareInterface
{
    use \VuFind\Log\LoggerAwareTrait;

    /**
     * Get handler name as string
     *
     * @var string
     *
     * @return string
     */
    public function getHandlerName(): string
    {
        return 'finna';
    }

    /**
     * Construct.
     *
     * @param ReservationList   $reservationList   Reservation list table
     * @param ResourceTable     $resource          Resource database table
     * @param UserResourceTable $userResourceTable UserResource table
     */
    public function __construct(
        protected ReservationList $reservationList,
        protected ResourceTable $resource,
        protected UserResourceTable $userResourceTable,
    ) {
    }

    /**
     * Check if the user has authority to access the list.
     *
     * @param User $user    User
     * @param int  $list_id List ID
     *
     * @return bool
     */
    public function hasAuthority(User $user, int $list_id): bool
    {
        return $this->reservationList->getExisting($list_id)->editAllowed($user);
    }

    /**
     * Add a new list.
     *
     * @param User   $user        User
     * @param string $title       Title
     * @param string $description Description
     * @param string $datasource  Datasource
     * @param string $building    Building
     *
     * @return int
     */
    public function addList(
        User $user,
        string $title,
        string $description,
        string $datasource,
        string $building
    ): int {
        return $this->reservationList->getNew($user)->updateFromRequest($user, new Parameters([
            'description' => $description,
            'title' => $title,
            'datasource' => $datasource,
            'building' => $building,
            'handler' => $this->getHandlerName(),
        ]));
    }

    /**
     * Retrieve all lists for a user.
     *
     * @param User $user User
     *
     * @return iterable
     */
    public function getLists(User $user): iterable
    {
        return $this->reservationList->select(['user_id' => $user->id]);
    }

    /**
     * Retrieve a list by ID.
     *
     * @param User $user    User
     * @param int  $list_id List ID
     *
     * @return iterable
     */
    public function getList(User $user, int $list_id): iterable
    {
        return $this->reservationList->getExisting($list_id)->toArray();
    }

    /**
     * Get lists containing a specific record.
     *
     * @param User   $user     User
     * @param string $recordId Record ID
     * @param string $source   Source
     *
     * @return iterable
     */
    public function getListsContaining(User $user, string $recordId, string $source = ''): iterable
    {
        return $this->reservationList->getListsContainingResource($recordId, $source, $user);
    }

    /**
     * Add an item to a list.
     *
     * @param User   $user        User
     * @param int    $list_id     List ID
     * @param string $recordId    Record ID
     * @param string $description Description
     * @param string $source      Source
     *
     * @return void
     */
    public function addItem(
        User $user,
        int $list_id,
        string $recordId,
        string $description = '',
        string $source = ''
    ): void {
        /**
         * Finna Resource Table
         *
         * @var \Finna\Db\Table\Resource
         */
        $resourceTable = $this->reservationList->getDbTable('Resource');
        $resource = $resourceTable->findResource($recordId, $source);

        /**
         * List to Resource
         *
         * @var \Finna\Db\Table\ReservationListResource
         */
        $reservationListResource = $this->reservationList->getDbTable(\Finna\Db\Table\ReservationListResource::class);
        $reservationListResource->createOrUpdateLink(
            $resource->id,
            $user->id,
            $list_id,
            $description
        );
    }

    /**
     * Order a list.
     *
     * @param User   $user        User
     * @param int    $list_id     List ID
     * @param string $pickup_date Pickup date
     *
     * @return bool
     */
    public function orderList(User $user, int $list_id, string $pickup_date): bool
    {
        $currentList = $this->reservationList->getExisting($list_id);
        $result = $currentList->setOrdered($user, $pickup_date);
        return !!$result;
    }

    /**
     * Delete a list.
     *
     * @param User $user    User
     * @param int  $list_id List ID
     *
     * @return bool
     */
    public function deleteList(User $user, int $list_id): bool
    {
        $currentList = $this->reservationList->getExisting($list_id);
        return !!$currentList->delete($user);
    }

    /**
     * Delete items from a list
     *
     * @param User  $user    User
     * @param int   $list_id List ID
     * @param array $ids     IDs to delete
     *
     * @return bool
     */
    public function deleteItems(User $user, int $list_id, array $ids): bool
    {
        // Sort $ids into useful array:
        $sorted = [];
        foreach ($ids as $current) {
            [$source, $id] = explode('|', $current, 2);
            if (!isset($sorted[$source])) {
                $sorted[$source] = [];
            }
            $sorted[$source][] = $id;
        }

        $list = $this->reservationList->getExisting($list_id);
        foreach ($sorted as $source => $ids) {
            $list->removeResourcesById($user, $ids, $source);
        }
        return false;
    }

    /**
     * Get items for a list
     *
     * @param User $user    User
     * @param int  $list_id List ID
     *
     * @return iterable
     */
    public function getItems(User $user, int $list_id): iterable
    {
        /**
         * Finna Resource Table
         *
         * @var \Finna\Db\Table\Resource
         */
        $resourceTable = $this->reservationList->getDbTable(\Finna\Db\Table\Resource::class);
        return $resourceTable->getReservationResources($user->id, $list_id);
    }

    /**
     * Get items for a list as a string
     *
     * @param User $user    User
     * @param int  $list_id List ID
     *
     * @return string
     */
    public function getItemsAsString(User $user, int $list_id): string
    {
        return '';
    }
}
