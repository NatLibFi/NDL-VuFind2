<?php

/**
 * AJAX handler to comment on a record.
 *
 * PHP version 8
 *
 * Copyright (C) The National Library of Finland 2015-2024.
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
 * along with this program; if not, see
 * <https://www.gnu.org/licenses/>.
 *
 * @category VuFind
 * @package  AJAX
 * @author   Samuli Sillanpää <samuli.sillanpaa@helsinki.fi>
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development Wiki
 */

namespace Finna\AjaxHandler;

use DateTime;
use Finna\Db\Service\CommentsServiceInterface;
use Laminas\Mvc\Controller\Plugin\Params;
use VuFind\Config\AccountCapabilities;
use VuFind\Controller\Plugin\Captcha;
use VuFind\Db\Entity\UserEntityInterface;
use VuFind\Ratings\RatingsService;
use VuFind\Record\Loader as RecordLoader;
use VuFind\Record\ResourcePopulator;
use VuFind\Search\SearchRunner;

use function assert;
use function count;
use function intval;

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
     * Constructor.
     *
     * @param ResourcePopulator        $resourcePopulator   Resource populator service
     * @param CommentsServiceInterface $commentsService     Comments database service
     * @param Captcha                  $captcha             Captcha controller plugin
     * @param ?UserEntityInterface     $user                Logged in user (or null)
     * @param bool                     $enabled             Are comments enabled?
     * @param RecordLoader             $recordLoader        Record loader
     * @param AccountCapabilities      $accountCapabilities Account capabilities helper
     * @param RatingsService           $ratingsService      Ratings service
     * @param array                    $config              VuFind configuration
     */
    public function __construct(
        ResourcePopulator $resourcePopulator,
        CommentsServiceInterface $commentsService,
        Captcha $captcha,
        ?UserEntityInterface $user,
        bool $enabled,
        RecordLoader $recordLoader,
        AccountCapabilities $accountCapabilities,
        RatingsService $ratingsService,
        protected array $config,
    ) {
        parent::__construct(
            $resourcePopulator,
            $commentsService,
            $captcha,
            $user,
            $enabled,
            $recordLoader,
            $accountCapabilities,
            $ratingsService
        );
    }

    /**
     * Search runner.
     *
     * @var ?SearchRunner
     */
    protected ?SearchRunner $searchRunner = null;

    /**
     * Setter for search runner.
     *
     * @param SearchRunner $runner Search runner
     *
     * @return void
     */
    public function setSearchRunner(SearchRunner $runner): void
    {
        $this->searchRunner = $runner;
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
        assert($this->commentsService instanceof CommentsServiceInterface);

        // Make sure comments are enabled:
        if (!$this->enabled) {
            return $this->formatResponse(
                $this->translate('Comments disabled'),
                self::STATUS_HTTP_BAD_REQUEST
            );
        }

        if (null === $this->user) {
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

        $resource = $this->resourcePopulator->getOrCreateResourceForRecordId($id, $source);
        if ($commentId = $params->fromPost('commentId')) {
            // Edit existing comment
            $this->commentsService->editComment($this->user, $commentId, $comment);
        } else {
            // Add new comment
            if (!$this->checkCaptcha()) {
                return $this->formatResponse(
                    $this->translate('captcha_not_passed'),
                    self::STATUS_HTTP_FORBIDDEN
                );
            }

            // Check if the user has exceeded the comment limit for the day:
            $limits = $this->config['Social']['daily_record_comment_limit'] ?? [];
            $commentLimit = $limits[$this->user->getAuthMethod()] ?? $limits['*'] ?? null;
            if (null !== $commentLimit) {
                $today = new DateTime('today');
                $commentCount = count(
                    $this->commentsService->findCommentsForRecordByUser($id, $source, $this->user, $today)
                );
                if ($commentCount >= $commentLimit) {
                    return $this->formatResponse(
                        $this->translate(
                            'comment_daily_limit_reached',
                            ['count' => $commentLimit],
                            useIcuFormatter: true
                        ),
                        self::STATUS_HTTP_NEED_AUTH
                    );
                }
            }

            $commentId = $this->commentsService->addComment(
                $comment,
                $this->user,
                $resource
            );

            // Add comment to deduplicated records
            $results = $this->searchRunner->run(
                ['lookfor' => 'local_ids_str_mv:"' . addcslashes($id, '"') . '"'],
                $source,
                function ($runner, $params, $searchId): void {
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

            $this->commentsService->addRecordLinks($commentId, $ids);
        }

        $rating = $params->fromPost('rating', '');
        if (
            $driver->isRatingAllowed()
            && ('' !== $rating
            || $this->accountCapabilities->isRatingRemovalAllowed())
        ) {
            $this->ratingsService->saveRating(
                $driver,
                $this->user->getId(),
                '' === $rating ? null : intval($rating)
            );
        }

        return $this->formatResponse(compact('commentId'));
    }
}
