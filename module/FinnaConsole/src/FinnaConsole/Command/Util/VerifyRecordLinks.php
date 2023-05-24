<?php

/**
 * Console service for verifying record links, resources and ratings.
 *
 * PHP version 8
 *
 * Copyright (C) The National Library of Finland 2016-2023.
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
 * @package  Service
 * @author   Samuli Sillanpää <samuli.sillanpaa@helsinki.fi>
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/vufind2:developer_manual Wiki
 */

namespace FinnaConsole\Command\Util;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use VuFind\Db\Row\Resource;

/**
 * Console service for verifying record links, resources and ratings.
 *
 * @category VuFind
 * @package  Service
 * @author   Samuli Sillanpää <samuli.sillanpaa@helsinki.fi>
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/vufind2:developer_manual Wiki
 */
class VerifyRecordLinks extends AbstractUtilCommand
{
    /**
     * The name of the command (the part after "public/index.php")
     *
     * @var string
     */
    protected static $defaultName = 'util/verify_record_links';

    /**
     * Comments table.
     *
     * @var \VuFind\Db\Table\Comments
     */
    protected $commentsTable;

    /**
     * CommentsRecord link table.
     *
     * @var \Finna\Db\Table\CommentsRecord
     */
    protected $commentsRecordTable;

    /**
     * Resource table.
     *
     * @var \VuFind\Db\Table\Resource
     */
    protected $resourceTable;

    /**
     * Ratings table
     *
     * @var \VuFind\Db\Table\Ratings
     */
    protected $ratingsTable;

    /**
     * Solr backend
     *
     * @var \VuFindSearch\Backend\Solr\Backend
     */
    protected $solr;

    /**
     * Record loader
     *
     * @var \VuFind\Record\Loader
     */
    protected $recordLoader;

    /**
     * Search config
     *
     * @var \Laminas\Config\Config
     */
    protected $searchConfig;

    /**
     * Record batch size to process at a time
     *
     * @var int
     */
    protected $batchSize = 1000;

    /**
     * Constructor
     *
     * @param \VuFind\Db\Table\Comments          $comments       Comments table
     * @param \Finna\Db\Table\CommentsRecord     $commentsRecord CommentsRecord table
     * @param \VuFind\Db\Table\Resource          $resource       Resource table
     * @param \VuFind\Db\Table\Ratings           $ratings        Ratings table
     * @param \VuFindSearch\Backend\Solr\Backend $solr           Search backend
     * @param \VuFind\Record\Loader              $recordLoader   Record loader
     * @param \Laminas\Config\Config             $searchConfig   Search config
     */
    public function __construct(
        \VuFind\Db\Table\Comments $comments,
        \Finna\Db\Table\CommentsRecord $commentsRecord,
        \VuFind\Db\Table\Resource $resource,
        \VuFind\Db\Table\Ratings $ratings,
        \VuFindSearch\Backend\Solr\Backend $solr,
        \VuFind\Record\Loader $recordLoader,
        \Laminas\Config\Config $searchConfig
    ) {
        $this->commentsTable = $comments;
        $this->commentsRecordTable = $commentsRecord;
        $this->resourceTable = $resource;
        $this->ratingsTable = $ratings;
        $this->solr = $solr;
        $this->recordLoader = $recordLoader;
        $this->recordLoader->setCacheContext(\VuFind\Record\Cache::CONTEXT_DISABLED);
        $this->searchConfig = $searchConfig;

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
                'resources',
                null,
                InputOption::VALUE_NEGATABLE,
                'Whether to process saved resources (records) -- default is true',
                true
            )
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
            $this->checkComments();
        }
        if ($input->getOption('ratings')) {
            $this->checkRatings();
        }
        if ($input->getOption('resources')) {
            $this->checkResources();
        }

        return 0;
    }

    /**
     * Check resources (records)
     *
     * @return void
     */
    protected function checkResources(): void
    {
        $this->msg('Checking saved Solr resources for moved records');
        $count = $fixed = 0;
        $lastId = null;
        $batch = [];
        do {
            $callback = function ($select) use ($lastId) {
                $select->where->equalTo('source', 'Solr');
                if (null !== $lastId) {
                    $select->where->greaterThan('id', $lastId);
                }
                $select->order('id');
                $select->limit($this->batchSize);
            };
            $lastId = null;
            $resources = $this->resourceTable->select($callback);
            foreach ($resources as $resource) {
                $lastId = $resource->id;
                $batch[] = $resource;
                if (count($batch) < 100) {
                    continue;
                }
                $fixed += $this->verifyResourceIds($batch);
                $count += count($batch);
                $batch = [];
                $msg = "$count resources checked, $fixed id's updated";
                if ($count % 1000 == 0) {
                    $this->msg($msg);
                } else {
                    $this->msg($msg, OutputInterface::VERBOSITY_VERY_VERBOSE);
                }
            }
        } while (null !== $lastId);
        if ($batch) {
            $fixed += $this->verifyResourceIds($batch);
            $count += count($batch);
        }
        $this->msg(
            "Resource checking completed with $count resources checked, $fixed"
            . " id's fixed"
        );
    }

    /**
     * Check comments
     *
     * @return void
     */
    protected function checkComments(): void
    {
        $this->msg('Checking comments');
        $count = $fixed = 0;
        $lastId = null;
        $batch = [];
        do {
            $callback = function ($select) use ($lastId) {
                if (null !== $lastId) {
                    $select->where->greaterThan('id', $lastId);
                }
                $select->order('id');
                $select->limit($this->batchSize);
                $select->columns(['id', 'resource_id']);
            };
            // Callback now has the old value of $lastId, reset it:
            $lastId = null;
            $comments = $this->commentsTable->select($callback);
            foreach ($comments as $comment) {
                $lastId = $comment->id;
                $resource = $this->resourceTable
                    ->select(['id' => $comment->resource_id])->current();
                if (!$resource || 'Solr' !== $resource->source) {
                    continue;
                }
                $batch[] = [
                    'commentId' => $comment->id,
                    'recordId' => $resource->record_id,
                ];
                if (count($batch) < 100) {
                    continue;
                }
                $fixed += $this->verifyCommentLinks($batch);
                $count += count($batch);
                $batch = [];
                $msg = "$count comments checked, $fixed links fixed";
                if ($count % 1000 == 0) {
                    $this->msg($msg);
                } else {
                    $this->msg($msg, OutputInterface::VERBOSITY_VERY_VERBOSE);
                }
            }
        } while (null !== $lastId);
        if ($batch) {
            $fixed += $this->verifyCommentLinks($batch);
            $count += count($batch);
        }
        $this->msg(
            "Comment check completed with $count comments checked, $fixed"
            . ' links fixed'
        );
    }

    /**
     * Check ratings
     *
     * @return void
     */
    protected function checkRatings(): void
    {
        $this->msg('Checking ratings');
        $count = $fixed = 0;
        $startDate = date('Y-m-d');
        $lastId = null;
        $batch = [];
        do {
            $callback = function ($select) use ($lastId) {
                if (null !== $lastId) {
                    $select->where->greaterThan('id', $lastId);
                }
                $select->where->notEqualTo('created', '2000-01-01 00:00:00');
                $select->order('id');
                $select->limit($this->batchSize);
                $select->columns(['id']);
            };
            // Callback now has the old value of $lastId, reset it:
            $lastId = null;
            $ratings = $this->ratingsTable->select($callback);
            foreach ($ratings as $current) {
                // Re-read the record since since may have changed:
                $rating = $this->ratingsTable->select(['id' => $current->id])
                    ->current();
                $lastId = $rating->id;
                if ($rating->finna_checked >= $startDate) {
                    continue;
                }
                $resource = $this->resourceTable
                    ->select(['id' => $rating->resource_id])->current();
                if (!$resource || 'Solr' !== $resource->source) {
                    continue;
                }
                $batch[] = [
                    'rating' => $rating,
                    'recordId' => $resource->record_id,
                ];
                if (count($batch) < 100) {
                    continue;
                }
                $fixed += $this->verifyRatings($batch);
                $count += count($batch);
                $batch = [];
                $msg = "$count ratings checked, $fixed links fixed";
                if ($count % 1000 == 0) {
                    $this->msg($msg);
                } else {
                    $this->msg($msg, OutputInterface::VERBOSITY_VERY_VERBOSE);
                }
            }
        } while (null !== $lastId);
        if ($batch) {
            $fixed += $this->verifyRatings($batch);
            $count += count($batch);
        }
        $this->msg(
            "Rating check completed with $count ratings checked, $fixed links fixed"
        );
    }

    /**
     * Verify comment links for a batch of comments
     *
     * @param array $batch Batch of commentId + recordId
     *
     * @return int Number of comments fixed
     */
    protected function verifyCommentLinks(array $batch): int
    {
        $recordIds = array_column($batch, 'recordId');
        $allIds = $this->getDedupRecordIds($recordIds);

        $fixed = 0;
        foreach ($batch as $current) {
            $commentId = $current['commentId'];
            $recordId = $current['recordId'];
            // This preserves the comment-record links for a comment when all
            // links point to non-existent records. Dangling links have no
            // effect in the UI. If a record was temporarily unavailable and
            // gets re-added to the index with the same ID, the comment is shown
            // in the UI again.
            $ids = $allIds[$recordId] ?? [$recordId];
            if ($this->commentsRecordTable->verifyLinks($commentId, $ids)) {
                ++$fixed;
            }
        }
        return $fixed;
    }

    /**
     * Verify ratings
     *
     * @param array $batch Batch of rating + recordId
     *
     * @return int Number of ratings fixed
     */
    protected function verifyRatings(array $batch): int
    {
        $recordIds = array_column($batch, 'recordId');
        $allIds = $this->getDedupRecordIds($recordIds);

        $fixed = 0;
        foreach ($batch as $current) {
            $rating = $current['rating'];
            $recordId = $current['recordId'];
            $ids = $allIds[$recordId] ?? [];
            if (!$allIds) {
                continue;
            }
            foreach ($ids as $id) {
                if ($id === $recordId) {
                    continue;
                }
                $resource = $this->resourceTable->findResource($id, 'Solr');
                if (!$resource) {
                    continue;
                }

                $targetRow = $this->ratingsTable->select(
                    [
                        'resource_id' => $resource->id,
                        'user_id' => $rating->user_id,
                    ]
                )->current();
                if ($targetRow) {
                    if ($targetRow->rating !== $rating->rating) {
                        ++$fixed;
                    }
                } else {
                    ++$fixed;
                    $targetRow = $this->ratingsTable->createRow();
                    $targetRow->user_id = $rating->user_id;
                    $targetRow->resource_id = $resource->id;
                }
                $targetRow->rating = $rating->rating;
                // Don't set creation date to indicate that this is a generated entry
                $targetRow->finna_checked = date('Y-m-d H:i:s');
                $targetRow->save();
            }
            $rating->finna_checked = date('Y-m-d H:i:s');
            $rating->save();
        }

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

    /**
     * Verify resource ids
     *
     * @param Resource[] $resources Resources to verify
     *
     * @return int Number of fixed resources
     */
    protected function verifyResourceIds(array $resources)
    {
        $ids = [];
        foreach ($resources as $resource) {
            $ids[] = [
                'id' => $resource->record_id,
                'source' => $resource->source,
            ];
        }
        try {
            $records = $this->recordLoader->loadBatch($ids, true);
        } catch (\Exception $e) {
            $this->warn(
                'Exception loading record batch: ' . $e->getMessage()
            );
            return false;
        }

        $fixed = 0;
        foreach ($records as $idx => $record) {
            $resource = $resources[$idx];
            if ($record instanceof \VuFind\RecordDriver\Missing) {
                $this->msg(
                    "Record missing for resource {$resource->id} record_id"
                        . " {$resource->source}:{$resource->record_id}",
                    OutputInterface::VERBOSITY_VERBOSE
                );
            }

            $id = $record->getUniqueId();
            if ($id != $resource->record_id) {
                $this->msg(
                    "Updating resource {$resource->id} record_id from"
                    . " {$resource->record_id} to $id"
                );
                $resource->record_id = $id;
                $resource->save();
                ++$fixed;
            }
        }
        return $fixed;
    }
}
