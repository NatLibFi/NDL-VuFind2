<?php

/**
 * Finna resource list resource entity interface.
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
 * @author   Juha Luoma <juha.luoma@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development Wiki
 */

namespace Finna\Db\Entity;

use DateTime;
use VuFind\Db\Entity\EntityInterface;
use VuFind\Db\Entity\ResourceEntityInterface;
use VuFind\Db\Entity\UserEntityInterface;

/**
 * Finna resource list resource entity interface.
 *
 * @category VuFind
 * @package  Db_Interface
 * @author   Juha Luoma <juha.luoma@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development Wiki
 *
 * @property int $id
 * @property int $resource_id
 * @property int $list_id
 * @property int $user_id
 * @property string $notes
 * @property string $saved
 */
interface FinnaResourceListResourceEntityInterface extends EntityInterface
{
    /**
     * Id getter
     *
     * @return ?int
     */
    public function getId(): ?int;

    /**
     * Set resource id
     *
     * @param ResourceEntityInterface $resource Resource entity
     *
     * @return FinnaResourceListResourceEntityInterface
     */
    public function setResource(ResourceEntityInterface $resource): FinnaResourceListResourceEntityInterface;

    /**
     * Get resource id
     *
     * @return int
     */
    public function getResourceId(): int;

    /**
     * Get list id
     *
     * @return int
     */
    public function getListId(): int;

    /**
     * Set list id
     *
     * @param FinnaResourceListEntityInterface $list List entity
     *
     * @return FinnaResourceListResourceEntityInterface
     */
    public function setList(FinnaResourceListEntityInterface $list): FinnaResourceListResourceEntityInterface;

    /**
     * Set saved
     *
     * @param DateTime $dateTime Created date
     *
     * @return FinnaResourceListResourceEntityInterface
     */
    public function setSaved(DateTime $dateTime): FinnaResourceListResourceEntityInterface;

    /**
     * Get saved
     *
     * @return DateTime
     */
    public function getSaved(): Datetime;

    /**
     * Set notes
     *
     * @param string $note Note
     *
     * @return FinnaResourceListResourceEntityInterface
     */
    public function setNotes(string $note): FinnaResourceListResourceEntityInterface;

    /**
     * Get notes
     *
     * @return string
     */
    public function getNotes(): string;

    /**
     * Get user id
     *
     * @return int
     */
    public function getUserId(): int;

    /**
     * Set user id from user entity
     *
     * @param UserEntityInterface $user User entity
     *
     * @return FinnaResourceListResourceEntityInterface
     */
    public function setUser(UserEntityInterface $user): FinnaResourceListResourceEntityInterface;
}
