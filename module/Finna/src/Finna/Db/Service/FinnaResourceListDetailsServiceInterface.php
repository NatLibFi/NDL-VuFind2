<?php

/**
 * Finna resource list details service interface
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
 * @package  Db_Service
 * @author   Juha Luoma <juha.luoma@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org   Main Site
 */

namespace Finna\Db\Service;

use Finna\Db\Entity\FinnaResourceListDetailsEntityInterface;
use Finna\Db\Entity\FinnaResourceListEntityInterface;
use VuFind\Db\Entity\UserEntityInterface;
use VuFind\Db\Service\DbServiceInterface;

/**
 * Finna resource list details service interface
 *
 * @category VuFind
 * @package  Db_Service
 * @author   Juha Luoma <juha.luoma@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org   Main Site
 */
interface FinnaResourceListDetailsServiceInterface extends DbServiceInterface
{
    /**
     * Create a FinnaResourceList entity object.
     *
     * @return FinnaResourceListDetailsEntityInterface
     */
    public function createEntity(): FinnaResourceListDetailsEntityInterface;

    /**
     * Delete setting for a list.
     *
     * @param FinnaResourceListEntityInterface|int $listOrId List entity object or ID to delete
     *
     * @return void
     */
    public function deleteFinnaResourceListSetting(FinnaResourceListEntityInterface|int $listOrId): void;

    /**
     * Retrieve settings by list or ID.
     *
     * @param FinnaResourceListEntityInterface|int $listOrId List entity object or ID to retrieve settings for
     *
     * @return ?FinnaResourceListDetailsEntityInterface
     */
    public function getFinnaResourceListDetailsById(
        FinnaResourceListEntityInterface|int $listOrId
    ): ?FinnaResourceListDetailsEntityInterface;

    /**
     * Retrieve settings by an array of list IDs and list type.
     *
     * @param int[]  $listIds  List IDs to retrieve settings for
     * @param string $listType List type to retrieve settings for
     *
     * @return FinnaResourceListDetailsEntityInterface[]
     */
    public function getFinnaResourceListDetailsByListIds(
        array $listIds,
        string $listType
    ): array;

    /**
     * Get finna resource list settings for user.
     *
     * @param UserEntityInterface|int $userOrId       User entity object or ID
     * @param string                  $listIdentifier Identifier of the list used by institution
     * @param string                  $institution    Institution name in yaml config
     * @param ?string                 $listType       List type to retrieve settings for or omit for all
     *
     * @return FinnaResourceListDetailsEntityInterface[]
     */
    public function getFinnaResourceListDetailsByUser(
        UserEntityInterface|int $userOrId,
        string $listIdentifier = '',
        string $institution = '',
        string $listType = null
    ): array;
}
