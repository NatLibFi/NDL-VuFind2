<?php

/**
 * Database service for Comments.
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
 * @package  Database
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:database_gateways Wiki
 */

namespace Finna\Db\Service;

use VuFind\Db\Entity\UserEntityInterface;
use VuFind\Db\Service\DbServiceAwareTrait;
use VuFind\Db\Table\DbTableAwareTrait;

use function is_int;

/**
 * Database service for Comments.
 *
 * @category VuFind
 * @package  Database
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:database_gateways Wiki
 */
class CommentsService extends \VuFind\Db\Service\CommentsService implements FinnaCommentsServiceInterface
{
    use DbServiceAwareTrait;
    use DbTableAwareTrait;

    /**
     * Mark comment as inappropriate
     *
     * @param ?UserEntityInterface $user      User object
     * @param int                  $commentId Comment ID
     * @param string               $reason    Reason
     * @param string               $message   Expand given reason
     * @param string               $sessionId Session ID
     *
     * @return void
     */
    public function markCommentInappropriate(
        ?UserEntityInterface $user,
        int $commentId,
        string $reason,
        string $message,
        string $sessionId
    ) {
        $table = $this->getDbTable('CommentsInappropriate');
        $row = $table->createRow();
        $row->user_id = $user?->getId();
        $row->comment_id = $commentId;
        $row->reason = $reason;
        $row->message = $message;
        $row->created = date('Y-m-d H:i:s');
        $row->session_id = $sessionId;
        $row->save();
    }

    /**
     * Edit comment.
     *
     * @param UserEntityInterface|int $userOrId  User object or identifier
     * @param int                     $commentId Comment ID
     * @param string                  $comment   Comment
     *
     * @return void
     */
    public function editComment(UserEntityInterface|int $userOrId, int $commentId, string $comment)
    {
        $this->getDbTable('comments')->update(
            [
                'comment' => $comment,
                'finna_updated' => date('Y-m-d H:i:s'),
            ],
            [
                'id' => $commentId,
                'user_id' => is_int($userOrId) ? $userOrId : $userOrId->getId(),
            ]
        );
    }

    /**
     * Change all matching comments to use the new resource ID instead of the old one (called when an ID changes).
     *
     * @param int   $commentId Comment ID
     * @param array $recordIds Record IDs
     *
     * @return void
     */
    public function addRecordLinks(int $commentId, array $recordIds): void
    {
        $this->getDbTable('CommentsRecord')->addLinks($commentId, $recordIds);
    }
}
