<?php

/**
 * Row Definition for user_list
 *
 * PHP version 8
 *
 * Copyright (C) The National Library of Finland 2016.
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
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org   Main Site
 */

namespace Finna\Db\Row;

use VuFind\Exception\ListPermission as ListPermissionException;
use VuFind\Exception\MissingField as MissingFieldException;
use VuFind\Db\Row\UserList;
use VuFind\Db\Row\RowGateway;
use Laminas\Session\Container;

/**
 * Row Definition for reservation_list
 *
 * @category VuFind
 * @package  Db_Row
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org   Main Site
 *
 * @property string $finna_updated
 */
class ReservationList extends UserList
{
    /**
     * Constructor
     *
     * @param \Laminas\Db\Adapter\Adapter $adapter   Database adapter
     * @param Tags                        $tagParser Tag parser
     * @param Container                   $session   Session container
     */
    public function __construct($adapter, Container $session = null)
    {
        $this->session = $session;
        // Parents parent
        RowGateway::__construct('id', 'finna_reservation_list', $adapter);
    }

    /**
     * Set list as ordered and save.
     *
     * 
     */
    public function setOrdered($user = false)
    {
        $this->finna_rl_ordered = date('Y-m-d H:i:s');
        return parent::save($user);
    }

    /**
     * Update and save the list object using a request object -- useful for
     * sharing form processing between multiple actions.
     *
     * @param \VuFind\Db\Row\User|bool   $user    Logged-in user (false if none)
     * @param \Laminas\Stdlib\Parameters $request Request to process
     *
     * @return int ID of newly created row
     * @throws ListPermissionException
     * @throws MissingFieldException
     */
    public function updateFromRequest($user, $request)
    {
        $this->title = $request->get('title');
        $this->description = $request->get('desc');
        $this->datasource = $request->get('datasource');
        $this->building = $request->get('building');
        $this->save($user);
        return $this->id;
    }

    /**
     * Get an array of tags assigned to this list.
     * Overwritten to return only an empty array if called.
     *
     * @return array
     */
    public function getListTags()
    {
        return [];
    }

    /**
     * Is this a public list?
     * Overwritten to return false.
     *
     * @return bool
     */
    public function isPublic()
    {
        return false;
    }

    /**
     * Destroy the list.
     *
     * @param \VuFind\Db\Row\User|bool $user  Logged-in user (false if none)
     * @param bool                     $force Should we force the delete without
     * checking permissions?
     *
     * @return int The number of rows deleted.
     */
    public function delete($user = false, $force = false)
    {
        if (!$force && !$this->editAllowed($user)) {
            throw new ListPermissionException('list_access_denied');
        }

        // Remove user_resource and resource_tags rows:
        $reservationListResource = $this->getDbTable(\Finna\Db\Table\ReservationListResource::class);
        $reservationListResource->destroyLinks(null, $this->user_id, $this->id);

        // Remove the list itself:
        return parent::delete();
    }
}
