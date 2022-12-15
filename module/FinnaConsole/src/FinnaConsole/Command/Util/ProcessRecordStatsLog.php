<?php
/**
 * Console service for processing record stats log table.
 *
 * PHP version 7
 *
 * Copyright (C) The National Library of Finland 2022.
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
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/vufind2:developer_manual Wiki
 */
namespace FinnaConsole\Command\Util;

use Finna\Db\Table\FinnaRecordStatsLog;
use Finna\Db\Table\FinnaRecordView;
use Finna\Db\Table\FinnaRecordViewInstView;
use Finna\Db\Table\FinnaRecordViewRecord;
use Finna\Db\Table\FinnaRecordViewRecordFormat;
use Finna\Db\Table\FinnaRecordViewRecordRights;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Console service for processing record stats log table.
 *
 * @category VuFind
 * @package  Service
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/vufind2:developer_manual Wiki
 */
class ProcessRecordStatsLog extends AbstractUtilCommand
{
    /**
     * The name of the command (the part after "public/index.php")
     *
     * @var string
     */
    protected static $defaultName = 'util/process_record_stats_log';

    /**
     * Record stats log table
     *
     * @var FinnaRecordStatsLog
     */
    protected $recordStatsLog;

    /**
     * Record view table
     *
     * @var FinnaRecordView
     */
    protected $recordView;

    /**
     * Record view institution data table
     *
     * @var FinnaRecordViewInstView
     */
    protected $recordViewInstView;

    /**
     * Record view record data table
     *
     * @var FinnaRecordViewRecord
     */
    protected $recordViewRecord;

    /**
     * Record view record format data table
     *
     * @var FinnaRecordViewRecordFormat
     */
    protected $recordViewRecordFormat;

    /**
     * Record view record usage rights data table
     *
     * @var FinnaRecordViewRecordRights
     */
    protected $recordViewRecordRights;

    /**
     * Formats cache
     *
     * @var array
     */
    protected $formatCache = [];

    /**
     * Usage rights cache
     *
     * @var array
     */
    protected $usageRightsCache = [];

    /**
     * Record batch size to process at a time
     *
     * @var int
     */
    protected $batchSize = 1000;

    /**
     * Constructor
     *
     * @param FinnaRecordStatsLog         $recordStatsLog         Record stats log
     * table
     * @param FinnaRecordView             $recordView             Record view table
     * @param FinnaRecordViewInstView     $recordViewInstView     Record view
     * institution data table
     * @param FinnaRecordViewRecord       $recordViewRecord       Record view record
     * data table
     * @param FinnaRecordViewRecordFormat $recordViewRecordFormat Record view record
     * format data table
     * @param FinnaRecordViewRecordRights $recordViewRecordRights Record view record
     * usage rights data table
     */
    public function __construct(
        FinnaRecordStatsLog $recordStatsLog,
        FinnaRecordView $recordView,
        FinnaRecordViewInstView $recordViewInstView,
        FinnaRecordViewRecord $recordViewRecord,
        FinnaRecordViewRecordFormat $recordViewRecordFormat,
        FinnaRecordViewRecordRights $recordViewRecordRights
    ) {
        $this->recordStatsLog = $recordStatsLog;
        $this->recordView = $recordView;
        $this->recordViewInstView = $recordViewInstView;
        $this->recordViewRecord = $recordViewRecord;
        $this->recordViewRecordFormat = $recordViewRecordFormat;
        $this->recordViewRecordRights = $recordViewRecordRights;

        parent::__construct();
    }

    /**
     * Configure the command.
     *
     * @return void
     */
    protected function configure()
    {
        $this->setDescription(
            'Process record stats log into records and hits tables'
        )->addOption(
            'batch-size',
            null,
            InputOption::VALUE_REQUIRED,
            'Set the processing batch size',
            $this->batchSize
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

        $this->batchSize = $input->getOption('batch-size');

        $this->msg(
            "Record stats log processing started (batch size $this->batchSize)"
        );

        $addCount = 0;
        $incCount = 0;
        $viewRecord = null;
        $viewInstView = null;
        do {
            $callback = function ($select) {
                $select->where->lessThan('date', date('Y-m-d'));
                $select->limit($this->batchSize);
            };
            $rows = 0;
            foreach ($this->recordStatsLog->select($callback) as $logEntry) {
                if (null === $viewRecord
                    || $viewRecord->backend !== $logEntry->backend
                    || $viewRecord->source !== $logEntry->source
                    || $viewRecord->record_id !== $logEntry->record_id
                ) {
                    $logEntryArr = $logEntry->toArray();
                    $logEntryArr['format_id']
                        = $this->getFormatId($logEntryArr['formats']);
                    $logEntryArr['usage_rights_id']
                        = $this->getUsageRightsId($logEntryArr['usage_rights']);
                    $viewRecord
                        = $this->recordViewRecord->getByLogEntry($logEntryArr);
                }
                if (null === $viewInstView
                    || $viewInstView->institution !== $logEntry->institution
                    || $viewInstView->view !== $logEntry->view
                ) {
                    $viewInstView
                        = $this->recordViewInstView->getByLogEntry($logEntry);
                }
                $viewFields = [
                    'inst_view_id' => $viewInstView->id,
                    'crawler' => $logEntry->crawler,
                    'date' => $logEntry->date,
                    'record_id' => $viewRecord->id,
                ];

                $rowsAffected = $this->recordView->update(
                    [
                        'count' => new \Laminas\Db\Sql\Literal(
                            'count + ' . $logEntry->count
                        )
                    ],
                    $viewFields,
                );
                if (0 === $rowsAffected) {
                    $hit = $this->recordView->createRow();
                    $hit->populate($viewFields);
                    $hit->count = $logEntry->count;
                    $hit->save();
                    ++$addCount;
                } else {
                    ++$incCount;
                }

                $logEntry->delete();

                ++$rows;
                $count = $addCount + $incCount;
                $msg = "$count log entries processed, $addCount add(s), $incCount"
                    . ' increment(s)';
                if ($count % 1000 == 0) {
                    $this->msg($msg);
                } else {
                    $this->msg($msg, OutputInterface::VERBOSITY_VERY_VERBOSE);
                }
            }
        } while ($rows);

        $count = $addCount + $incCount;
        $msg = "Completed with $count log entries processed, $addCount add(s),"
            . " $incCount increment(s)";
        $this->msg($msg);

        return 0;
    }

    /**
     * Get id for a formats string
     *
     * @param string $formats Formats
     *
     * @return int
     */
    protected function getFormatId(string $formats): int
    {
        if (!isset($this->formatCache[$formats])) {
            $this->formatCache[$formats]
                = $this->recordViewRecordFormat->getByFormat($formats)->id;
        }
        return $this->formatCache[$formats];
    }

    /**
     * Get id for a usage rights string
     *
     * @param string $usageRights Usage rights
     *
     * @return int
     */
    protected function getUsageRightsId(string $usageRights): int
    {
        if (!isset($this->usageRightsCache[$usageRights])) {
            $this->usageRightsCache[$usageRights]
                = $this->recordViewRecordRights->getByUsageRights($usageRights)->id;
        }
        return $this->usageRightsCache[$usageRights];
    }
}
