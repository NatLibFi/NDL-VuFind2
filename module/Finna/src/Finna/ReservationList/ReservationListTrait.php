<?php

/**
 * Reservation List Trait
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
 * @package  ReservationList
 * @author   Juha Luoma <juha.luoma@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org   Main Site
 */

namespace Finna\ReservationList;

use Laminas\Db\ResultSet\ResultSetInterface;

/**
 * Reservation list trait
 *
 * @category VuFind
 * @package  Controller
 * @author   Juha Luoma <juha.luoma@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org Main Site
 */
trait ReservationListTrait
{
    protected $reservationListService;

    /**
     * Sets the ReservationListService.
     *
     * @param ReservationListService $service ReservationListService to set.
     *
     * @return void
     */
    public function setReservationListService(ReservationListService $service): void
    {
        $this->reservationListService = $service;
    }

    /**
     * Retrieves reservation lists.
     *
     * @return array Reservation lists.
     */
    public function getReservationLists(): array
    {
        return $this->reservationListService->getListsForUser($this);
    }

    /**
     * Retrieves reservation lists as objects.
     *
     * @return ResultInterface Reservation lists as objects.
     */
    public function getReservationListsAsObjects(): ResultSetInterface
    {
        return $this->reservationListService->getListsForUserAsObjects($this);
    }

    /**
     * Retrieves the reservation list contained in a specific record and source.
     *
     * @param string $recordId ID of the record.
     * @param string $source   Source of the reservation list.
     *
     * @return array The reservation list contained in the specified record and source.
     */
    public function getReservationListContainedIn(string $recordId, string $source): array
    {
        return $this->reservationListService->getListsContaining($this, $recordId, $source);
    }
}
