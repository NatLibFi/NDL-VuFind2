<?php

/**
 * Finna resource list resource entity interface.
 *
 * PHP version 8
 *
 * Copyright (C) The National Library of Finland 2024.
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2,
 * as published by the Free Software Foundation.
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301  USA
 *
 * @category VuFind
 * @package  Finna_Db
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
 * @package  Finna_Db
 * @author   Juha Luoma <juha.luoma@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development Wiki
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
     * Resource ID setter
     *
     * @param ResourceEntityInterface $resource Resource entity
     *
     * @return FinnaResourceListResourceEntityInterface
     */
    public function setResource(ResourceEntityInterface $resource): FinnaResourceListResourceEntityInterface;

    /**
     * Resource ID getter
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
     * Created setter
     *
     * @param DateTime $dateTime Created date
     *
     * @return FinnaResourceListResourceEntityInterface
     */
    public function setSaved(DateTime $dateTime): FinnaResourceListResourceEntityInterface;

    /**
     * Created getter
     *
     * @return DateTime
     */
    public function getSaved(): Datetime;

    /**
     * Data setter
     *
     * @param string $note Note
     *
     * @return FinnaResourceListResourceEntityInterface
     */
    public function setNotes(string $note): FinnaResourceListResourceEntityInterface;

    /**
     * Data getter
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
     * Set user
     *
     * @param UserEntityInterface $user User entity
     *
     * @return FinnaResourceListResourceEntityInterface
     */
    public function setUser(UserEntityInterface $user): FinnaResourceListResourceEntityInterface;
}
