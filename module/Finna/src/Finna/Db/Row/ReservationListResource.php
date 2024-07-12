<?php

/**
 * Table Definition for finna_reservation_list_resource
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
 * @package  Db_Table
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @author   Juha Luoma <juha.luoma@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org Main Page
 */

namespace Finna\Db\Row;

use VuFind\Db\Row\RowGateway;

/**
 * Table Definition for finna_reservation_list_resource
 *
 * @category VuFind
 * @package  Db_Table
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @author   Juha Luoma <juha.luoma@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org Main Page
 *
 * @property int     $id
 * @property int     $user_id
 * @property int     $resource_id
 * @property ?int    $list_id
 * @property ?string $notes
 * @property string  $saved
 */
class ReservationListResource extends RowGateway
{
    /**
     * Constructor
     *
     * @param \Laminas\Db\Adapter\Adapter $adapter Database adapter
     */
    public function __construct($adapter)
    {
        parent::__construct('id', 'finna_reservation_list_resource', $adapter);
    }

        /**
     * Given an array of item ids, remove them from all lists
     *
     * @param \VuFind\Db\Row\User|bool $user   Logged-in user (false if none)
     * @param array                    $ids    IDs to remove from the list
     * @param string                   $source Type of resource identified by IDs
     *
     * @return void
     */
    public function removeResourcesById(
        $user,
        $ids,
        $source = DEFAULT_SEARCH_BACKEND
    ) {
        if (!$this->editAllowed($user ?: null)) {
            throw new ListPermissionException('list_access_denied');
        }

        // Retrieve a list of resource IDs:
        $resourceTable = $this->getDbTable('Resource');
        $resources = $resourceTable->findResources($ids, $source);

        $resourceIDs = [];
        foreach ($resources as $current) {
            $resourceIDs[] = $current->id;
        }

        // Remove Resource (related tags are also removed implicitly)
        $userResourceTable = $this->getDbTable('UserResource');
        $userResourceTable->destroyLinks(
            $resourceIDs,
            $this->user_id,
            $this->id
        );
    }
}
