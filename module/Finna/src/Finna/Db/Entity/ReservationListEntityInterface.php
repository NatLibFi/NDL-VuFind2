<?php

/**
 * Row Definition for finna_reservation_list
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
 * @package  Db_Row
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @author   Juha Luoma <juha.luoma@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org   Main Site
 */

namespace Finna\Db\Entity;

use VuFind\Db\Entity\EntityInterface;
use VuFind\Db\Entity\UserEntityInterface;
use VuFind\Exception\ListPermission as ListPermissionException;
use VuFind\Exception\MissingField as MissingFieldException;

/**
 * Row Definition for finna_reservation_list
 *
 * @category VuFind
 * @package  Db_Row
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @author   Juha Luoma <juha.luoma@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org   Main Site
 *
 * @property int    $id
 * @property int    $user_id
 * @property string $title
 * @property string $datasource
 * @property string $description
 * @property string $building
 * @property string $created
 * @property bool   $public
 * @property string $ordered
 * @property string $pickup_date
 * @property string $handler
 */
interface ReservationListEntityInterface extends EntityInterface
{
    public function getId(): int;

    /**
     * Sets the ordered data for the reservation list.
     *
     * @param User   $user        User or false.
     * @param string $pickup_date Set pickup date
     *
     * @return mixed
     */
    public function setOrdered($user, $pickup_date);

    /**
     * Update and save the list object using a request object -- useful for
     * sharing form processing between multiple actions.
     *
     * @param UserEntityInterface|bool   $user    Logged-in user (false if none)
     * @param \Laminas\Stdlib\Parameters $request Request to process
     *
     * @return int ID of newly created row
     * @throws ListPermissionException
     * @throws MissingFieldException
     */
    public function updateFromRequest($user, $request): int;

    /**
     * Is the current user allowed to edit this list?
     *
     * @param UserEntityInterface $user Logged-in user
     *
     * @return bool
     */
    public function editAllowed($user): bool;

    /**
     * Destroy the list.
     *
     * @param UserEntityInterface|bool $user  Logged-in user (false if none)
     * @param bool                     $force Should we force the delete without
     * checking permissions?
     *
     * @return int The number of rows deleted.
     */
    public function delete($user = false, $force = false): int;

    /**
     * Saves the properties to the database.
     *
     * This performs an intelligent insert/update, and reloads the
     * properties with fresh data from the table on success.
     *
     * @param UserEntityInterface|bool $user Logged-in user (false if none)
     *
     * @return mixed The primary key value(s), as an associative array if the
     *     key is compound, or a scalar if the key is single-column.
     * @throws ListPermissionException
     * @throws MissingFieldException
     */
    public function save($user = false): array|int;

    /**
     * Remember that this list was used so that it can become the default in
     * dialog boxes.
     *
     * @return void
     */
    public function rememberLastUsed(): void;

    /**
     * Given an array of item ids, remove them from this list
     *
     * @param UserEntityInterface $user   Logged-in user (false if none)
     * @param array               $ids    IDs to remove from the list
     * @param string              $source Type of resource identified by IDs
     *
     * @return void
     */
    public function removeResourcesById($user, $ids, $source = DEFAULT_SEARCH_BACKEND): void;
}
