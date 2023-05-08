<?php

/**
 * AJAX handler to comment on a record.
 *
 * PHP version 7
 *
 * Copyright (C) The National Library of Finland 2015-2023.
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
 * @package  AJAX
 * @author   Samuli Sillanpää <samuli.sillanpaa@helsinki.fi>
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development Wiki
 */

namespace Finna\AjaxHandler;

use Finna\Db\Table\CommentsRecord;
use Laminas\Mvc\Controller\Plugin\Params;
use VuFind\Config\AccountCapabilities;
use VuFind\Controller\Plugin\Captcha;
use VuFind\Db\Row\User;
use VuFind\Db\Table\Comments;
use VuFind\Db\Table\Resource;
use VuFind\Record\Loader as RecordLoader;
use VuFind\Search\SearchRunner;

/**
 * AJAX handler to comment on a record.
 *
 * @category VuFind
 * @package  AJAX
 * @author   Samuli Sillanpää <samuli.sillanpaa@helsinki.fi>
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development Wiki
 */
class CommentRecord extends \VuFind\AjaxHandler\CommentRecord
{
    /**
     * Comments table
     *
     * @var Comments
     */
    protected $commentsTable;

    /**
     * CommentsRecord table
     *
     * @var CommentsRecord
     */
    protected $commentsRecordTable;

    /**
     * Search runner
     *
     * @var SearchRunner
     */
    protected $searchRunner;

    /**
     * Constructor
     *
     * @param Resource            $table          Resource database table
     * @param Captcha             $captcha        Captcha controller plugin
     * @param User|bool           $user           Logged in user (or false)
     * @param bool                $enabled        Are comments enabled?
     * @param RecordLoader        $loader         Record loader
     * @param AccountCapabilities $ac             Account capabilities helper
     * @param Comments            $comments       Comments table
     * @param CommmentsRecord     $commentsRecord CommentsRecord table
     * @param SearchRunner        $searchRunner   Search runner
     */
    public function __construct(
        Resource $table,
        Captcha $captcha,
        $user,
        $enabled,
        RecordLoader $loader,
        AccountCapabilities $ac,
        Comments $comments = null,
        CommentsRecord $commentsRecord = null,
        SearchRunner $searchRunner = null
    ) {
        parent::__construct($table, $captcha, $user, $enabled, $loader, $ac);
        $this->commentsTable = $comments;
        $this->commentsRecordTable = $commentsRecord;
        $this->searchRunner = $searchRunner;
    }

    /**
     * Handle a request.
     *
     * @param Params $params Parameter helper from controller
     *
     * @return array [response data, HTTP status code]
     */
    public function handleRequest(Params $params)
    {
        // Make sure comments are enabled:
        if (!$this->enabled) {
            return $this->formatResponse(
                $this->translate('Comments disabled'),
                self::STATUS_HTTP_BAD_REQUEST
            );
        }

        if ($this->user === false) {
            return $this->formatResponse(
                $this->translate('You must be logged in first'),
                self::STATUS_HTTP_NEED_AUTH
            );
        }

        $id = $params->fromPost('id');
        $source = $params->fromPost('source', DEFAULT_SEARCH_BACKEND);
        $comment = $params->fromPost('comment');
        if (empty($id) || empty($comment)) {
            return $this->formatResponse(
                $this->translate('bulk_error_missing'),
                self::STATUS_HTTP_BAD_REQUEST
            );
        }
        $driver = $this->recordLoader->load($id, $source, false);

        $resource = $this->table->findResource($id, $source);
        if ($commentId = $params->fromPost('commentId')) {
            // Edit existing comment
            $this->commentsTable->edit($this->user->id, $commentId, $comment);
        } else {
            // Add new comment
            if (!$this->checkCaptcha()) {
                return $this->formatResponse(
                    $this->translate('captcha_not_passed'),
                    self::STATUS_HTTP_FORBIDDEN
                );
            }

            $commentId = $resource->addComment($comment, $this->user);

            // Add comment to deduplicated records
            $results = $this->searchRunner->run(
                ['lookfor' => 'local_ids_str_mv:"' . addcslashes($id, '"') . '"'],
                $source,
                function ($runner, $params, $searchId) {
                    $params->setLimit(1000);
                    $params->setPage(1);
                    $params->resetFacetConfig();
                    $options = $params->getOptions();
                    $options->disableHighlighting();
                    $options->spellcheckEnabled(false);
                }
            );
            $ids = [$id];

            if (
                !$results instanceof \VuFind\Search\EmptySet\Results
                && count($results->getResults())
            ) {
                $results = $results->getResults();
                $ids = reset($results)->getLocalIds();
            }
            $ids[] = $id;
            $ids = array_values(array_unique($ids));

            $this->commentsRecordTable->addLinks($commentId, $ids);
        }

        $rating = $params->fromPost('rating', '');
        if (
            $driver->isRatingAllowed()
            && ('' !== $rating
            || $this->accountCapabilities->isRatingRemovalAllowed())
        ) {
            $driver->addOrUpdateRating(
                $this->user->id,
                '' === $rating ? null : intval($rating)
            );
        }

        return $this->formatResponse(compact('commentId'));
    }
}
