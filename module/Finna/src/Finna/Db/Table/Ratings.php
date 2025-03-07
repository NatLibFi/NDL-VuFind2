<?php

/**
 * Table Definition for ratings
 *
 * PHP version 8
 *
 * Copyright (C) The National Library of Finland 2025.
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
 * @author   Jaro Ravila <jaro.ravila@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org Main Site
 */

namespace Finna\Db\Table;

/**
 * Table Definition for ratings
 *
 * @category VuFind
 * @package  Db_Table
 * @author   Jaro Ravila <jaro.ravila@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org Main Site
 */
class Ratings extends \VuFind\Db\Table\Ratings
{
    /**
     * Delete comments by given user and comment ids
     *
     * @param array $ids    Array of comment ids
     * @param int   $userId User ID 
     *
     * @return void
     */
    public function deleteByIdsAndUserId(array $ids, int $userId): void
    {
        $callback = function ($select) use ($ids, $userId) {
            $select->where->in('id', $ids);
            $select->where->equalTo('user_id', $userId);
        };
        $this->delete($callback);
    }
}