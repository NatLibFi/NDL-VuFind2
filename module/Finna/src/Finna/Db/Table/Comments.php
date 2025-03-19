<?php

/**
 * Table Definition for comments
 *
 * PHP version 8
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

use Laminas\Db\Sql\Select;

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
     * @param int    $userId    Current user ID
     * @param string $id        Record ID
     * @param string $reason    Reason
     * @param string $message   Expand given reason
     * @param string $sessionId Session ID
     *
     * @return void
     */
    public function markInappropriate($userId, $id, $reason, $message, $sessionId)
    {
        $table = $this->getDbTable('CommentsInappropriate');
        $row = $table->createRow();
        $row->user_id = $userId;
        $row->comment_id = $id;
        $row->reason = $reason;
        $row->message = $message;
        $row->created = date('Y-m-d H:i:s');
        $row->session_id = $sessionId;
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
                'finna_updated' => date('Y-m-d H:i:s'),
            ],
            [
                'id' => $id,
                'user_id' => $userId,
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

    /**
     * Get a paginated result of all comments and ratings
     * made by the user
     *
     * @param int $userId User ID
     * @param int $limit  Limit
     * @param int $page   Page
     *
     * @return \Laminas\Paginator\Paginator
     */
    public function getByUserId($userId, $limit, $page)
    {
        $sql = $this->getSql();

        // Get all comments, record IDs and ratings if present for the record
        $commentSelect = new Select();
        $commentSelect->from('comments')
            ->where->equalTo('comments.user_id', $userId);
        $commentSelect->columns([
            'resource_id',
            'comment_id' => 'id',
            'comment',
            'user_id',
            'created',
            'finna_visible',
        ])->join(
            ['r' => 'ratings'],
            'r.resource_id = comments.resource_id AND r.user_id = comments.user_id',
            [
                'rating_id' => 'id',
                'rating',
                'rating_user_id' => 'user_id',
                'rating_created' => 'created',
            ],
            Select::JOIN_LEFT
        )
        ->join(
            ['re' => 'resource'],
            'comments.resource_id = re.id',
            ['record_id', 'source'],
            Select::JOIN_LEFT
        );

        // Get all ratings that don't have a comment for the same resource
        $commentSub = new Select();
        $commentSub->from('comments')
            ->columns(['resource_id'])
            ->where->equalTo('comments.user_id', $userId);

        $ratingSelect = new Select();
        $ratingSelect->from('ratings')
        ->columns([
            'resource_id',
            'comment_id' => null,
            'comment' => null,
            'user_id',
            'created',
            'finna_visible' => null,
            'rating_id' => 'id',
            'rating',
            'rating_user_id' => 'user_id',
            'rating_created' => 'created',
        ])
        ->join(
            ['re' => 'resource'],
            'ratings.resource_id = re.id',
            ['record_id', 'source'],
            Select::JOIN_LEFT
        )
        ->where->equalTo('ratings.user_id', $userId)
        ->where->notIn('ratings.resource_id', $commentSub);

        $commentSelect->combine($ratingSelect, Select::COMBINE_UNION);

        $paginatedSelect = new Select();
        $paginatedSelect->from(['c' => $commentSelect]);
        $paginatedSelect->order('created DESC');
        if ($page > 0) {
            $paginatedSelect->offset($page);
        }
        if (null !== $limit) {
            $paginatedSelect->limit($limit);
        }

        $adapter = new \Laminas\Paginator\Adapter\LaminasDb\DbSelect($paginatedSelect, $sql);
        $paginator = new \Laminas\Paginator\Paginator($adapter);
        $paginator->setItemCountPerPage($limit);
        if (null !== $page) {
            $paginator->setCurrentPageNumber($page);
        }
        return $paginator;
    }
}
