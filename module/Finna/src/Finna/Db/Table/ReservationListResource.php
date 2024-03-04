<?php

/**
 * Table Definition for finna_reservation_list_resource
 *
 * PHP version 8
 *
 * Copyright (C) The National Library of Finland 2016-2019.
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
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org Main Page
 */

namespace Finna\Db\Table;

use Laminas\Db\Sql\Expression;
/**
 * Table Definition for finna_reservation_list_resource
 *
 * @category VuFind
 * @package  Db_Table
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org Main Page
 */
class ReservationListResource extends \VuFind\Db\Table\UserResource
{
    /**
     * Create link if one does not exist; update notes if one does.
     *
     * @param string $resource_id ID of resource to link up
     * @param string $user_id     ID of user creating link
     * @param string $list_id     ID of list to link up
     * @param string $notes       Notes to associate with link
     * @param int    $order       Custom order index for the resource in list
     *
     * @return void
     */
    public function createOrUpdateLink(
        $resource_id,
        $user_id,
        $list_id,
        $notes = '',
        $order = null
    ) {
        $row = parent::createOrUpdateLink($resource_id, $user_id, $list_id, $notes);
        $row->save();
        $this->updateListDate($list_id, $user_id);
    }

    /**
     * Unlink rows for the specified resource.  This will also automatically remove
     * any tags associated with the relationship.
     *
     * @param string|array $resource_id ID (or array of IDs) of resource(s) to
     * unlink (null for ALL matching resources)
     * @param string       $user_id     ID of user removing links
     * @param string       $list_id     ID of list to unlink
     * (null for ALL matching lists, with the destruction of all tags associated
     * with the $resource_id value; true for ALL matching lists, but retaining
     * any tags associated with the $resource_id independently of lists)
     *
     * @return void
     */
    public function destroyLinks($resource_id, $user_id, $list_id = null)
    {
        parent::destroyLinks($resource_id, $user_id, $list_id);
        if (null !== $list_id && true !== $list_id) {
            $this->updateListDate($list_id, $user_id);
        }
    }

    /**
     * Update the date of a list
     *
     * @param string $listId ID of list to unlink
     * @param string $userId ID of user removing links
     *
     * @return void
     */
    protected function updateListDate($listId, $userId)
    {
        $userTable = $this->getDbTable('User');
        $user = $userTable->select(['id' => $userId])->current();
        if (empty($user)) {
            return;
        }
        $listTable = $this->getDbTable(\Finna\Db\Table\ReservationList::class);
        $list = $listTable->getExisting($listId);
        if (empty($list->title)) {
            // Save throws an exception unless the list has a title
            $list->title = '-';
        }
        $list->save($user);
    }
}
