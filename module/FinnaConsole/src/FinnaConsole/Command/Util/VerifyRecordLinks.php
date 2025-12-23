<?php

/**
 * Console service for verifying record links, resources and ratings.
 *
 * PHP version 8
 *
 * Copyright (C) The National Library of Finland 2016-2024.
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
 * @package  Service
 * @author   Samuli Sillanpää <samuli.sillanpaa@helsinki.fi>
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development Wiki
 */

namespace FinnaConsole\Command\Util;

use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Finna\Db\Entity\RatingsEntityInterface;
use Finna\Db\Service\CommentsServiceInterface;
use Finna\Db\Service\FinnaCommentsRecordServiceInterface;
use Finna\Db\Service\RatingsServiceInterface;
use Finna\Db\Service\RecordServiceInterface;
use Finna\Db\Service\ResourceServiceInterface;
use Finna\Record\ResourcePopulator;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use VuFind\Record\Loader as RecordLoader;
use VuFindSearch\Backend\Solr\Backend as SolrBackend;

use function assert;
use function count;
use function in_array;

/**
 * Console service for verifying record links, resources and ratings.
 *
 * @category VuFind
 * @package  Service
 * @author   Samuli Sillanpää <samuli.sillanpaa@helsinki.fi>
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development Wiki
 */
#[AsCommand(
    name: 'util/verify_record_links'
)]
class VerifyRecordLinks extends AbstractUtilCommand
{
    /**
     * Record batch size to process at a time
     *
     * @var int
     */
    protected $batchSize = 100;

    /**
     * Constructor
     *
     * @param EntityManagerInterface              $entityManager              Entity manager
     * @param RecordServiceInterface              $recordService              Record database service
     * @param CommentsServiceInterface            $commentsService            Comments service
     * @param FinnaCommentsRecordServiceInterface $finnaCommentsRecordService Comments service
     * @param RatingsServiceInterface             $ratingsService             Ratings service
     * @param ResourceServiceInterface            $resourceService            Resource service
     * @param ResourcePopulator                   $resourcePopulator          Resource populator
     * @param SolrBackend                         $solr                       Search backend
     * @param RecordLoader                        $recordLoader               Record loader
     * @param array                               $searchConfig               Search config
     */
    public function __construct(
        protected EntityManagerInterface $entityManager,
        protected RecordServiceInterface $recordService,
        protected CommentsServiceInterface $commentsService,
        protected FinnaCommentsRecordServiceInterface $finnaCommentsRecordService,
        protected RatingsServiceInterface $ratingsService,
        protected ResourceServiceInterface $resourceService,
        protected ResourcePopulator $resourcePopulator,
        protected \VuFindSearch\Backend\Solr\Backend $solr,
        protected RecordLoader $recordLoader,
        protected array $searchConfig
    ) {
        $recordLoader->setCacheContext(\VuFind\Record\Cache::CONTEXT_DISABLED);

        parent::__construct();
    }

    /**
     * Configure the command.
     *
     * @return void
     */
    protected function configure()
    {
        $this->setDescription('Verify and update record links in the database')
            ->addOption(
                'comments',
                null,
                InputOption::VALUE_NEGATABLE,
                'Whether to process comments -- default is true',
                true
            )
            ->addOption(
                'ratings',
                null,
                InputOption::VALUE_NEGATABLE,
                'Whether to process ratings -- default is true',
                true
            );
    }

    /**
     * Run the command.
     *
     * @param InputInterface  $input  Input object
     * @param OutputInterface $output Output object
     *
     * @return int 0 for success
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->output = $output;

        $this->msg('Record link verification started');

        if ($input->getOption('comments')) {
            $this->checkCommentLinks();
        }

        if ($input->getOption('ratings')) {
            $this->checkRatingLinks();
        }

        return 0;
    }

    /**
     * Check comment links
     *
     * @return void
     */
    protected function checkCommentLinks(): void
    {
        $this->msg('Checking comments');
        $count = $fixed = 0;
        $lastId = null;
        do {
            $comments = $this->commentsService->getEntityBatch($lastId, $this->batchSize);
            $lastId = null;

            $batch = [];
            foreach ($comments as $comment) {
                $lastId = $comment->getId();
                $resource = $comment->getResource();
                if (!$resource || 'Solr' !== $resource->getSource()) {
                    continue;
                }
                $batch[] = [
                    'comment' => $comment,
                    'recordId' => $resource->getRecordId(),
                ];
            }
            if ($batch) {
                $fixed += $this->verifyCommentLinkBatch($batch);
                $count += count($batch);
                $this->msg("$count comments checked, $fixed links fixed");
            }
            $this->entityManager->clear();
        } while (null !== $lastId);
        $this->msg("Comment check completed with $count comments checked, $fixed links fixed");
    }

    /**
     * Verify comment links for a batch of comments
     *
     * @param array $batch Batch to process
     *
     * @return int Number of comments fixed
     */
    protected function verifyCommentLinkBatch(array $batch): int
    {
        $recordIds = array_column($batch, 'recordId');
        $allIds = $this->getDedupRecordIds($recordIds);

        $fixed = 0;
        foreach ($batch as $current) {
            $comment = $current['comment'];
            $recordId = $current['recordId'];
            // This preserves the comment-record links for a comment when all
            // links point to non-existent records. Dangling links have no
            // effect in the UI. If a record was temporarily unavailable and
            // gets re-added to the index with the same ID, the comment is shown
            // in the UI again.
            $recordIds = $allIds[$recordId] ?? [$recordId];

            $linkedRecordIds = [];

            // Remove any orphaned links
            $commentLinks = $this->finnaCommentsRecordService->findByComment($comment);
            foreach ($commentLinks as $link) {
                $linkRecordId = $link->getRecordId();
                if (!in_array($linkRecordId, $recordIds)) {
                    $this->entityManager->remove($link);
                    ++$fixed;
                } else {
                    $linkedRecordIds[] = $linkRecordId;
                }
            }

            // Add missing links
            $missingRecordIds = array_diff($recordIds, $linkedRecordIds);
            foreach ($missingRecordIds as $recordId) {
                $link = $this->finnaCommentsRecordService->createEntity();
                $link->setComment($comment)
                    ->setRecordId($recordId);
                $this->entityManager->persist($link);
                ++$fixed;
            }
        }
        $this->entityManager->flush();
        return $fixed;
    }

    /**
     * Check rating links
     *
     * @return void
     */
    protected function checkRatingLinks(): void
    {
        $this->msg('Checking ratings');
        $count = $fixed = 0;
        $startDate = new DateTime();
        $lastId = null;
        $batch = [];
        do {
            $ratings = $this->ratingsService->getEntityBatch($lastId, $this->batchSize);
            $lastId = null;

            foreach ($ratings as $rating) {
                assert($rating instanceof RatingsEntityInterface);
                // Re-read the record since since it may have changed:
                $this->entityManager->refresh($rating);
                $lastId = $rating->getId();
                if ($rating->getFinnaChecked() >= $startDate) {
                    continue;
                }

                $resource = $rating->getResource();
                if ('Solr' !== $resource->getSource()) {
                    continue;
                }
                $batch[] = [
                    'rating' => $rating,
                    'recordId' => $resource->getRecordId(),
                ];
            }
            if ($batch) {
                $fixed += $this->verifyRatingLinkBatch($batch);
                $count += count($batch);
                $batch = [];
                $this->msg("$count ratings checked, $fixed links fixed");
            }
            $this->entityManager->clear();
        } while (null !== $lastId);
        $this->msg("Rating check completed with $count ratings checked, $fixed links fixed");
    }

    /**
     * Verify ratings
     *
     * @param array $batch Batch of rating + recordId
     *
     * @return int Number of ratings fixed
     */
    protected function verifyRatingLinkBatch(array $batch): int
    {
        $recordIds = array_column($batch, 'recordId');
        $allIds = $this->getDedupRecordIds($recordIds);
        $fixed = 0;
        foreach ($batch as $current) {
            $rating = $current['rating'];
            $recordId = $current['recordId'];
            $ids = $allIds[$recordId] ?? [];
            if (!$allIds || !($user = $rating->getUser())) {
                continue;
            }
            foreach ($ids as $id) {
                if ($id === $recordId) {
                    continue;
                }
                // Avoid resourcePopulator's getOrCreateResourceForRecordId because it will call entity manager's
                // flush():
                $resource = $this->resourceService->getResourceByRecordId($id, 'Solr');
                if (null === $resource) {
                    $resource = $this->resourcePopulator->createResourceForRecordId($id, 'Solr');
                    $this->entityManager->persist($resource);
                }
                $targetRating = $this->ratingsService->getByResourceAndUser($resource, $user);
                if ($targetRating) {
                    if ($targetRating->getRating() !== $rating->getRating()) {
                        ++$fixed;
                    }
                } else {
                    ++$fixed;
                    $targetRating = $this->ratingsService->createEntity();
                    $targetRating
                        ->setResource($resource)
                        ->setUser($user);
                }
                $targetRating->setRating($rating->getRating());
                // Don't set creation date to indicate that this is a generated entry
                $targetRating->setFinnaChecked(new DateTime());
                $this->entityManager->persist($targetRating);
            }
            $rating->setFinnaChecked(new DateTime());
            $this->entityManager->persist($rating);
        }
        $this->entityManager->flush();
        return $fixed;
    }

    /**
     * Get IDs of duplicate records (including the given record)
     *
     * @param array $recordIds Record IDs
     *
     * @return array Associative array of arrays with record ID as the key
     */
    protected function getDedupRecordIds(array $recordIds): array
    {
        // Search directly in Solr to avoid any listeners or filters from interfering
        $escapedIds = array_map(
            function ($i) {
                return '"' . addcslashes($i, '"') . '"';
            },
            $recordIds
        );

        $query = new \VuFindSearch\Query\Query();
        $params = new \VuFindSearch\ParamBag(
            [
                'hl' => 'false',
                'spellcheck' => 'false',
                'sort' => '',
                'q' => 'local_ids_str_mv:(' . implode(' OR ', $escapedIds) . ')',
            ]
        );
        $records = $this->solr->search($query, 0, 1000, $params)->getRecords();

        $result = [];
        foreach ($records as $record) {
            $localIds = $record->getLocalIds();
            foreach ($recordIds as $id) {
                if (in_array($id, $localIds)) {
                    $result[$id] = $localIds;
                    break;
                }
            }
        }
        return $result;
    }
}
