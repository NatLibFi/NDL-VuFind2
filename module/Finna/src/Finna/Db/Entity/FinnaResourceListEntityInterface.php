<?php

/**
 * Finna resource list entity interface
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
 * Finna resource list entity interface
 *
 * @category VuFind
 * @package  Db_Interface
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @author   Juha Luoma <juha.luoma@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org   Main Site
 *
 * @property int    $id
 * @property int    $user_id
 * @property string $title
 * @property string $description
 * @property string $created
 * @property string $institution
 * @property string $list_config_identifier
 * @property string $list_type
 * @property string $ordered
 * @property string $pickup_date
 * @property string $connection
 */
interface FinnaResourceListEntityInterface extends EntityInterface
{
    /**
     * Get the ID of the list.
     *
     * @return int
     */
    public function getId(): int;

    /**
     * Get user id
     *
     * @return int
     */
    public function getUserId(): int;

    /**
     * Get user entity
     *
     * @return ?UserEntityInterface
     */
    public function getUser(): ?UserEntityInterface;

    /**
     * Set user
     *
     * @param UserEntityInterface $user User entity
     *
     * @return FinnaResourceListEntityInterface
     */
    public function setUser(UserEntityInterface $user): FinnaResourceListEntityInterface;

    /**
     * Set title.
     *
     * @param string $title Title
     *
     * @return FinnaResourceListEntityInterface
     */
    public function setTitle(string $title): FinnaResourceListEntityInterface;

    /**
     * Get title.
     *
     * @return string
     */
    public function getTitle(): string;

    /**
     * Get description.
     *
     * @return string
     */
    public function getDescription(): string;

    /**
     * Set description.
     *
     * @param string $description Description
     *
     * @return FinnaResourceListEntityInterface
     */
    public function setDescription(string $description = ''): FinnaResourceListEntityInterface;

    /**
     * Created setter
     *
     * @param DateTime $dateTime Created date
     *
     * @return FinnaResourceListEntityInterface
     */
    public function setCreated(Datetime $dateTime): FinnaResourceListEntityInterface;

    /**
     * Created getter
     *
     * @return DateTime
     */
    public function getCreated(): DateTime;

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
     * @return FinnaResourceListEntityInterface
     */
    public function setInstitution(string $institution): FinnaResourceListEntityInterface;

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
     * @return FinnaResourceListEntityInterface
     */
    public function setListConfigIdentifier(string $listConfigIdentifier): FinnaResourceListEntityInterface;

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
     * @return FinnaResourceListEntityInterface
     */
    public function setListType(string $listType): FinnaResourceListEntityInterface;

    /**
     * Get the ordered flag
     *
     * @return ?DateTime
     */
    public function getOrdered(): ?DateTime;

    /**
     * Set the ordered flag
     *
     * @return FinnaResourceListEntityInterface
     */
    public function setOrdered(): FinnaResourceListEntityInterface;

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
     * @return FinnaResourceListEntityInterface
     */
    public function setPickupDate(DateTime $pickupDate): FinnaResourceListEntityInterface;

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
     * @return FinnaResourceListEntityInterface
     */
    public function setConnection(string $connection): FinnaResourceListEntityInterface;
}
