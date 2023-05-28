<?php

/**
 * Console service for processing record stats log table.
 *
 * PHP version 8
 *
 * Copyright (C) The National Library of Finland 2022-2023.
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
use Finna\Statistics\Driver\Database as DatabaseDriver;
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
     * Statistics database driver
     *
     * @var DatabaseDriver;
     */
    protected $dbHandler;

    /**
     * Record batch size to process at a time
     *
     * @var int
     */
    protected $batchSize = 1000;

    /**
     * Constructor
     *
     * @param FinnaRecordStatsLog $recordStatsLog Record stats log table
     * @param DatabaseDriver      $dbHandler      Statistics database driver
     */
    public function __construct(
        FinnaRecordStatsLog $recordStatsLog,
        DatabaseDriver $dbHandler
    ) {
        $this->recordStatsLog = $recordStatsLog;
        $this->dbHandler = $dbHandler;

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

        $count = 0;
        do {
            $callback = function ($select) {
                $select->where->lessThan('date', date('Y-m-d'));
                $select->limit($this->batchSize);
            };
            $rows = 0;
            foreach ($this->recordStatsLog->select($callback) as $logEntry) {
                $this->dbHandler->addDetailedRecordViewEntry($logEntry->toArray());

                $logEntry->delete();

                ++$rows;
                ++$count;
                $msg = "$count log entries processed";
                if ($count % 1000 == 0) {
                    $this->msg($msg);
                } else {
                    $this->msg($msg, OutputInterface::VERBOSITY_VERY_VERBOSE);
                }
            }
        } while ($rows);

        $this->msg("Completed with $count log entries processed");
        return 0;
    }
}
