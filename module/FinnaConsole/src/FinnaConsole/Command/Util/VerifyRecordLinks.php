<?php
/**
 * Console service for verifying record links.
 *
 * PHP version 7
 *
 * Copyright (C) The National Library of Finland 2016-2020.
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
 * Console service for verifying record links.
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
     * @param \VuFindSearch\Backend\Solr\Backend $solr           Search backend
     * @param \VuFind\Record\Loader              $recordLoader   Record loader
     * @param \Laminas\Config\Config             $searchConfig   Search config
     */
    public function __construct(
        \VuFind\Db\Table\Comments $comments,
        \Finna\Db\Table\CommentsRecord $commentsRecord,
        \VuFind\Db\Table\Resource $resource,
        \VuFindSearch\Backend\Solr\Backend $solr,
        \VuFind\Record\Loader $recordLoader,
        \Laminas\Config\Config $searchConfig
    ) {
        $this->commentsTable = $comments;
        $this->commentsRecordTable = $commentsRecord;
        $this->resourceTable = $resource;
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

        // Check search config and warn about conflicting settings
        if (!empty($this->searchConfig['Records']['sources'])) {
            $this->warn(
                'Records/sources set in searches.ini.'
                . ' This may interfere with link verification.'
            );
        }
        if (!empty($this->searchConfig['HiddenFilters'])) {
            $this->warn(
                'HiddenFilters set in searches.ini.'
                . ' This may interfere with link verification.'
            );
        }
        if (!empty($this->searchConfig['RawHiddenFilters'])) {
            $this->warn(
                'RawHiddenFilters set in searches.ini.'
                . ' This may interfere with link verification.'
            );
        }

        if ($input->getOption('resources')) {
            $this->checkResources();
        }
        if ($input->getOption('comments')) {
            $this->checkComments();
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
                if ($this->verifyResourceId($resource)) {
                    ++$fixed;
                }
                ++$count;
                $msg = "$count resources checked, $fixed id's updated";
                if ($count % 1000 == 0) {
                    $this->msg($msg);
                } else {
                    $this->msg($msg, OutputInterface::VERBOSITY_VERY_VERBOSE);
                }
            }
        } while (null !== $lastId);
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
        do {
            $callback = function ($select) use ($lastId) {
                if (null !== $lastId) {
                    $select->where->greaterThan('id', $lastId);
                }
                $select->order('id');
                $select->limit($this->batchSize);
                $select->columns(['id', 'resource_id']);
            };
            $lastId = null;
            $comments = $this->commentsTable->select($callback);
            foreach ($comments as $comment) {
                $lastId = $comment->id;
                $resource = $this->resourceTable
                    ->select(['id' => $comment->resource_id])->current();
                if (!$resource || 'Solr' !== $resource->source) {
                    continue;
                }
                if ($this->verifyLinks($comment->id, $resource->record_id)) {
                    ++$fixed;
                }
                ++$count;
                $msg = "$count comments checked, $fixed links fixed";
                if ($count % 1000 == 0) {
                    $this->msg($msg);
                } else {
                    $this->msg($msg, OutputInterface::VERBOSITY_VERY_VERBOSE);
                }
            }
        } while (null !== $lastId);
        $this->msg(
            "Comment checking completed with $count comments checked, $fixed"
            . ' links fixed'
        );
    }

    /**
     * Verify links for a record
     *
     * @param int    $commentId Comment ID
     * @param string $recordId  Record ID
     *
     * @return bool True if changes were made
     */
    protected function verifyLinks($commentId, $recordId)
    {
        // Search directly in Solr to avoid any listeners or filters from interfering
        $query = new \VuFindSearch\Query\Query(
            'local_ids_str_mv:"' . addcslashes($recordId, '"') . '"'
        );

        $params = new \VuFindSearch\ParamBag(
            ['hl' => 'false', 'spellcheck' => 'false', 'sort' => '']
        );
        $records = $this->solr->search($query, 0, 1, $params)->getRecords();

        // This preserves the comment-record links for a comment when all
        // links point to non-existent records. Dangling links have no
        // effect in the UI. If a record was temporarily unavailable and
        // gets re-added to the index with the same ID, the comment is shown
        // in the UI again.
        if ($records) {
            $ids = $records[0]->getLocalIds();
        } else {
            $ids = [$recordId];
        }
        if ($this->commentsRecordTable->verifyLinks($commentId, $ids)) {
            return true;
        }
        return false;
    }

    /**
     * Verify resource id
     *
     * @param Resource $resource Resource
     *
     * @return bool True if changes were made
     */
    protected function verifyResourceId(Resource $resource)
    {
        try {
            $record = $this->recordLoader
                ->load($resource->record_id, $resource->source, true);
        } catch (\Exception $e) {
            $this->warn(
                "Exception loading record {$resource->source}:{$resource->record_id}"
                    . ':' . $e->getMessage()
            );
            return false;
        }

        if ($record instanceof \VuFind\RecordDriver\Missing) {
            $this->msg(
                "Record missing for resource {$resource->id} record_id"
                    . " {$resource->source}:{$resource->record_id}",
                OutputInterface::VERBOSITY_VERBOSE
            );
            return false;
        }

        $id = $record->getUniqueId();
        if ($id != $resource->record_id) {
            $this->msg(
                "Updating resource {$resource->id} record_id from"
                . " {$resource->record_id} to $id"
            );
            $resource->record_id = $id;
            $resource->save();
            return true;
        }
        return false;
    }
}
