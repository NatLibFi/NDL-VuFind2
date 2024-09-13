<?php

/**
 * Table Definition for finna_resource_list_resource
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

namespace Finna\Db\Table;

use Finna\Db\Entity\FinnaResourceListEntityInterface;
use Laminas\Db\Adapter\Adapter;
use Laminas\Db\Sql\Expression;
use Laminas\Db\Sql\Select;
use VuFind\Db\Row\RowGateway;
use VuFind\Db\Table\PluginManager;

use function is_array;

/**
 * Table Definition for finna_resource_list_resource
 *
 * @category VuFind
 * @package  Db_Table
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @author   Juha Luoma <juha.luoma@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org Main Page
 */
class FinnaResourceListResource extends \VuFind\Db\Table\Gateway
{
    /**
     * Constructor
     *
     * @param Adapter       $adapter Database adapter
     * @param PluginManager $tm      Table manager
     * @param array         $cfg     Laminas configuration
     * @param RowGateway    $rowObj  Row prototype object (null for default)
     * @param string        $table   Name of database table to interface with
     */
    public function __construct(
        Adapter $adapter,
        PluginManager $tm,
        $cfg,
        ?RowGateway $rowObj = null,
        $table = 'finna_resource_list_resource'
    ) {
        parent::__construct($adapter, $tm, $cfg, $rowObj, $table);
    }

    /**
     * Create link if one does not exist; update notes if one does.
     *
     * @param string $resource_id ID of resource to link up
     * @param string $user_id     ID of user creating link
     * @param string $list_id     ID of list to link up
     * @param string $notes       Notes to associate with link
     *
     * @return void
     */
    public function createOrUpdateLink(
        $resource_id,
        $user_id,
        $list_id,
        $notes = ''
    ) {
        $params = [
            'resource_id' => $resource_id, 'list_id' => $list_id,
            'user_id' => $user_id,
        ];
        $result = $this->select($params)->current();

        // Only create row if it does not already exist:
        if (empty($result)) {
            $result = $this->createRow();
            $result->resource_id = $resource_id;
            $result->list_id = $list_id;
            $result->user_id = $user_id;
        }

        // Update the notes:
        $result->notes = $notes;
        $result->save();
        $this->updateListDate($list_id, $user_id);
    }

    /**
     * Unlink rows for the specified resource. This will also automatically remove
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
        if (null !== $list_id && true !== $list_id) {
            $this->updateListDate($list_id, $user_id);
        }

        // Now build the where clause to figure out which rows to remove:
        // Do not destroy resource, if it is present in user_list
        $callback = function ($select) use ($resource_id, $user_id, $list_id) {
            $select->where->equalTo('user_id', $user_id);
            if (null !== $resource_id) {
                if (!is_array($resource_id)) {
                    $resource_id = [$resource_id];
                }
                $select->where->in('resource_id', $resource_id);
            }
            if (null !== $list_id && true !== $list_id) {
                $select->where->equalTo('list_id', $list_id);
            }
        };

        // Delete the rows:
        $this->delete($callback);
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
        /**
         * Reservation List Table
         *
         * @var \Finna\Db\Table\FinnaResourceList
         */
        $listTable = $this->getDbTable(\Finna\Db\Table\FinnaResourceList::class);
        /**
         * Reservation List row
         *
         * @var \Finna\Db\Row\FinnaResourceList
         */
        $list = $listTable->getExisting($listId);
        if (empty($list->title)) {
            // Save throws an exception unless the list has a title
            $list->title = '-';
        }
        $list->save($user);
    }

    /**
     * Get records for a list.
     *
     * @param FinnaResourceListEntityInterface $list List entity
     *
     * @return \Laminas\Db\ResultSet\AbstractResultSet
     */
    public function getRecordsForList(FinnaResourceListEntityInterface $list): \Laminas\Db\ResultSet\AbstractResultSet
    {
        $listId = $list->getId();
        $callback = function ($select) use ($listId) {
            $select->columns(
                [
                    new Expression(
                        'DISTINCT(?)',
                        ['finna_resource_list_resource.id'],
                        [Expression::TYPE_IDENTIFIER]
                    ), Select::SQL_STAR,
                ]
            );
            $select->join(
                ['r' => 'resource'],
                'r.id = finna_resource_list_resource.resource_id',
                []
            );
            $select->where->equalTo('finna_resource_list_resource.list_id', $listId);
        };
        return $this->select($callback);
    }
}
