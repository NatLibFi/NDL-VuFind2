<?php
/**
 * Table Definition for comments
 *
 * PHP version 7
 *
 * Copyright (C) The National Library of Finland 2015.
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
 * @author   Mika Hatakka <mika.hatakka@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org   Main Site
 */
namespace Finna\Db\Table;

/**
 * Table Definition for comments
 *
 * @category VuFind
 * @package  Db_Table
 * @author   Mika Hatakka <mika.hatakka@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org   Main Site
 */
class Comments extends \VuFind\Db\Table\Comments
{
    /**
     * Get tags associated with the specified resource.
     * Added email to result.
     *
     * @param string $id     Record ID to look up
     * @param string $source Source of record to look up
     *
     * @return array|\Laminas\Db\ResultSet\AbstractResultSet
     */
    public function getForResource($id, $source = DEFAULT_SEARCH_BACKEND)
    {
        $callback = $this->getResourceCallback($id);
        return $this->select($callback);
    }

    /**
     * Get tags associated with the specified resource by user.
     *
     * @param string $id     Record ID to look up
     * @param int    $userId User ID
     *
     * @return array|\Laminas\Db\ResultSet\AbstractResultSet
     */
    public function getForResourceByUser($id, $userId)
    {
        $callback = $this->getResourceCallback($id, $userId);
        return $this->select($callback);
    }

    /**
     * Mark comment as inappropriate
     *
     * @param int    $userId  Current user ID
     * @param int    $id      Record ID
     * @param string $reason  Reason
     * @param string $message Expand given reason
     *
     * @return void
     */
    public function markInappropriate($userId, $id, $reason, $message)
    {
        $table = $this->getDbTable('CommentsInappropriate');
        $row = $table->createRow();
        $row->user_id = $userId;
        $row->comment_id = $id;
        $row->reason = $reason;
        $row->message = $message;
        $row->created = date('Y-m-d H:i:s');
        $row->save();
    }

    /**
     * Edit comment.
     *
     * @param int    $userId  Current user ID
     * @param string $id      Record ID
     * @param string $comment Comment
     *
     * @return void
     */
    public function edit($userId, $id, $comment)
    {
        $this->update(
            [
                'comment' => $comment,
                'finna_updated' => date('Y-m-d H:i:s')
            ],
            [
                'id' => $id,
                'user_id' => $userId
            ]
        );
    }

    /**
     * Utility function for constructing a callback function used
     * by getForResource and getForResourceByUser.
     *
     * @param string $id     Record ID to look up
     * @param int    $userId User ID
     *
     * @return function
     */
    protected function getResourceCallback($id, $userId = false)
    {
        $callback = function ($select) use ($id, $userId) {
            $select->columns(['*']);
            $select->join(
                ['u' => 'user'],
                'u.id = comments.user_id',
                ['firstname', 'lastname', 'email'],
                $select::JOIN_LEFT
            );
            $select->join(
                ['cr' => 'finna_comments_record'],
                'comments.id = cr.comment_id',
                []
            );
            $select->where->equalTo('cr.record_id', $id);
            $select->where->equalTo('comments.finna_visible', 1);
            if ($userId !== false) {
                $select->where->equalTo('u.id', $userId);
            }
            $select->order('comments.created');
        };
        return $callback;
    }
}
