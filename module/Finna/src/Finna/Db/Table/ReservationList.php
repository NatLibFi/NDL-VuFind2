<?php

/**
 * Table Definition for finna_reservation_list
 *
 * PHP version 8.1
 *
 * Copyright (C) Villanova University 2024.
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
 * @author   Juha Luoma <juha.luoma@helsinki.fi>
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org Main Page
 */

namespace Finna\Db\Table;

use Laminas\Db\Adapter\Adapter;
use Laminas\Db\Sql\Expression;
use Laminas\Db\Sql\Select;
use Laminas\Session\Container;
use VuFind\Db\Row\RowGateway;
use VuFind\Db\Table\Gateway;
use VuFind\Db\Table\PluginManager;
use VuFind\Db\Table\UserList;
use VuFind\Exception\LoginRequired as LoginRequiredException;
use VuFind\Exception\RecordMissing as RecordMissingException;

/**
 * Table Definition for finna_reservation_list
 *
 * @category VuFind
 * @package  Db_Table
 * @author   Juha Luoma <juha.luoma@helsinki.fi>
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org Main Page
 */
class ReservationList extends UserList
{

    protected ?Container $session = null;

    /**
     * Constructor
     *
     * @param Adapter       $adapter Database adapter
     * @param PluginManager $tm      Table manager
     * @param array         $cfg     Laminas configuration
     * @param RowGateway    $rowObj  Row prototype object (null for default)
     * @param Container     $session Session container (must use same
     * namespace as container provided to \VuFind\View\Helper\Root\UserList).
     * @param string        $table   Name of database table to interface with
     */
    public function __construct(
        Adapter $adapter,
        PluginManager $tm,
        $cfg,
        ?RowGateway $rowObj = null,
        $session = null,
        $table = 'finna_reservation_list'
    ) {
        Gateway::__construct($adapter, $tm, $cfg, $rowObj, $table);
        $this->session = $session;
    }

    /**
     * Create a new list object.
     *
     * @param mixed $user User object representing owner of
     * new list (or false if not logged in)
     *
     * @return \Finna\Db\Row\ReservationList
     * @throws LoginRequiredException
     */
    public function getNew($user)
    {
        if (!$user) {
            throw new LoginRequiredException('Log in to create lists.');
        }

        $row = $this->createRow();
        $row->created = date('Y-m-d H:i:s');    // force creation date
        $row->user_id = $user->id;
        $row->datasource = '';
        return $row;
    }

    /**
     * Retrieve a list object.
     *
     * @param int $id Numeric ID for existing list.
     *
     * @return \Finna\Db\Row\ReservationList
     * @throws RecordMissingException
     */
    public function getExisting($id)
    {
        $result = $this->select(['id' => $id])->current();
        if (empty($result)) {
            throw new RecordMissingException('Cannot load list ' . $id);
        }
        return $result;
    }

    /**
     * Get lists containing a specific user_resource
     *
     * @param string $resourceId ID of record being checked.
     * @param string $source     Source of record to look up
     * @param User   $user       Optional user (to limit results to a particular
     * user).
     *
     * @return array
     */
    public function getListsContainingResource(
        $resourceId,
        $source = DEFAULT_SEARCH_BACKEND,
        $user = null
    ) {
        $userId = $user->id;
        // Set up base query:
        $callback = function ($select) use ($resourceId, $source, $userId) {
            $select->columns(
                [
                    new Expression(
                        'DISTINCT(?)',
                        ['finna_reservation_list.id'],
                        [Expression::TYPE_IDENTIFIER]
                    ), Select::SQL_STAR,
                ]
            );
            $select->join(
                ['rl' => 'finna_reservation_list_resource'],
                'rl.list_id = finna_reservation_list.id',
                []
            );
            $select->join(
                ['r' => 'resource'],
                'r.id = rl.resource_id',
                []
            );
            $select->where->equalTo('r.source', $source)
                ->equalTo('r.record_id', $resourceId);
            $select->order(['title']);

            if (null !== $userId) {
                $select->where->equalTo('rl.user_id', $userId);
            }
        };
        return $this->select($callback);
    }
}
