<?php

/**
 * Resource list details entity interface
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
 * @package  Db_Interface
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @author   Juha Luoma <juha.luoma@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org   Main Site
 */

namespace Finna\Db\Entity;

use DateTime;
use VuFind\Db\Entity\EntityInterface;
use VuFind\Db\Entity\UserEntityInterface;

/**
 * Resource list details entity interface
 *
 * @category VuFind
 * @package  Db_Interface
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @author   Juha Luoma <juha.luoma@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org   Main Site
 *
 * @property int    $id
 * @property string $institution
 * @property int    $list_id
 * @property string $list_config_identifier
 * @property string $list_type
 * @property string $ordered
 * @property string $pickup_date
 * @property string $connection
 * @property int    $user_id
 */
interface FinnaResourceListDetailsEntityInterface extends EntityInterface
{
    /**
     * Get the ID
     *
     * @return int
     */
    public function getId(): int;

    /**
     * Get the list ID
     *
     * @return int
     */
    public function getListId(): int;

    /**
     * Set the list ID
     *
     * @param int $listId List ID
     *
     * @return self
     */
    public function setListId(int $listId): self;

    /**
     * Get the institution
     *
     * @return string
     */
    public function getInstitution(): string;

    /**
     * Set the institution
     *
     * @param string $institution Institution
     *
     * @return self
     */
    public function setInstitution(string $institution): self;

    /**
     * Get the list configuration identifier
     *
     * @return string
     */
    public function getListConfigIdentifier(): string;

    /**
     * Set the list configuration identifier
     *
     * @param string $listConfigIdentifier List configuration identifier
     *
     * @return self
     */
    public function setListConfigIdentifier(string $listConfigIdentifier): self;

    /**
     * Get the list type
     *
     * @return string
     */
    public function getListType(): string;

    /**
     * Set the list type
     *
     * @param string $listType List type
     *
     * @return self
     */
    public function setListType(string $listType): self;

    /**
     * Get the ordered flag
     *
     * @return ?DateTime
     */
    public function getOrdered(): ?DateTime;

    /**
     * Set the ordered flag
     *
     * @return self
     */
    public function setOrdered(): self;

    /**
     * Get the pickup date
     *
     * @return ?DateTime
     */
    public function getPickupDate(): ?DateTime;

    /**
     * Set the pickup date
     *
     * @param DateTime $pickupDate Pickup date
     *
     * @return self
     */
    public function setPickupDate(DateTime $pickupDate): self;

    /**
     * Get the connection
     *
     * @return string
     */
    public function getConnection(): string;

    /**
     * Set the connection
     *
     * @param string $connection Connection
     *
     * @return self
     */
    public function setConnection(string $connection): self;

    /**
     * Get the user ID.
     *
     * @return int
     */
    public function getUserId(): int;

    /**
     * Set the user ID.
     *
     * @param int $userId User ID
     *
     * @return FinnaResourceListDetailsEntityInterface
     */
    public function setUserId(int $userId): FinnaResourceListDetailsEntityInterface;

    /**
     * Set user id from user entity.
     *
     * @param UserEntityInterface $user User entity
     *
     * @return FinnaResourceListDetailsEntityInterface
     */
    public function setUser(UserEntityInterface $user): FinnaResourceListDetailsEntityInterface;
}
