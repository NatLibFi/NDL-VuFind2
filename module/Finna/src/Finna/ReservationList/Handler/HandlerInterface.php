<?php

/**
 * Interface HandlerInterface
 *
 * PHP version 8.1
 *
 * Copyright (C) National Library of Finland 2024.
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

use VuFind\Db\Row\User;

/**
 * Interface HandlerInterface
 *
 * @category VuFind
 * @package  ReservationList
 * @author   Juha Luoma <juha.luoma@helsinki.fi>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development Wiki
 */
interface HandlerInterface
{
    /**
     * Get handler name as string
     *
     * @var string
     *
     * @return string
     */
    public function getHandlerName(): string;

    /**
     * Check if the user has authority to access the list.
     *
     * @param User $user    User
     * @param int  $list_id List ID
     *
     * @return bool
     */
    public function hasAuthority(User $user, int $list_id): bool;

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
    public function addList(User $user, string $title, string $description, string $datasource, string $building): int;

    /**
     * Retrieve all lists for a user.
     *
     * @param User $user User
     *
     * @return iterable
     */
    public function getLists(User $user): iterable;

    /**
     * Retrieve a list by ID.
     *
     * @param User $user    User
     * @param int  $list_id List ID
     *
     * @return iterable
     */
    public function getList(User $user, int $list_id): iterable;

    /**
     * Get lists containing a specific record.
     *
     * @param User   $user     User
     * @param string $recordId Record ID
     * @param string $source   Source
     *
     * @return iterable
     */
    public function getListsContaining(User $user, string $recordId, string $source = ''): iterable;

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
    ): void;

    /**
     * Order a list.
     *
     * @param User   $user        User
     * @param int    $list_id     List ID
     * @param string $pickup_date Pickup date
     *
     * @return bool
     */
    public function orderList(User $user, int $list_id, string $pickup_date): bool;

    /**
     * Delete a list.
     *
     * @param User $user    User
     * @param int  $list_id List ID
     *
     * @return bool
     */
    public function deleteList(User $user, int $list_id): bool;

    /**
     * Delete items from a list
     *
     * @param User  $user    User
     * @param int   $list_id List ID
     * @param array $ids     IDs to delete
     *
     * @return bool
     */
    public function deleteItems(User $user, int $list_id, array $ids): bool;

    /**
     * Get items for a list
     *
     * @param User $user    User
     * @param int  $list_id List ID
     *
     * @return iterable
     */
    public function getItems(User $user, int $list_id): iterable;

    /**
     * Get items for a list as a string
     *
     * @param User $user    User
     * @param int  $list_id List ID
     *
     * @return string
     */
    public function getItemsAsString(User $user, int $list_id): string;
}
