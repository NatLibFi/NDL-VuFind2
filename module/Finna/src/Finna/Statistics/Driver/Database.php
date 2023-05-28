<?php

/**
 * Database driver for statistics
 *
 * PHP version 8
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
 * @package  Statistics
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development Wiki
 */

namespace Finna\Statistics\Driver;

use Finna\Db\Table\FinnaPageViewStats;
use Finna\Db\Table\FinnaRecordStats;
use Finna\Db\Table\FinnaRecordStatsLog;
use Finna\Db\Table\FinnaRecordView;
use Finna\Db\Table\FinnaRecordViewInstView;
use Finna\Db\Table\FinnaRecordViewRecord;
use Finna\Db\Table\FinnaRecordViewRecordFormat;
use Finna\Db\Table\FinnaRecordViewRecordRights;
use Finna\Db\Table\FinnaSessionStats;
use Laminas\Db\TableGateway\AbstractTableGateway;
use Laminas\Log\LoggerAwareInterface;
use VuFind\Log\LoggerAwareTrait;

/**
 * Database driver for statistics
 *
 * @category VuFind
 * @package  Statistics
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development Wiki
 */
class Database implements DriverInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * Table for session statistics
     *
     * @var FinnaSessionStats
     */
    protected $sessionTable;

    /**
     * Table for page view statistics
     *
     * @var FinnaPageViewStats
     */
    protected $pageViewTable;

    /**
     * Table for record summary statistics
     *
     * @var FinnaRecordStats
     */
    protected $recordTable;

    /**
     * Table for record view log
     *
     * @var FinnaRecordStatsLog
     */
    protected $recordLogTable;

    /**
     * Record view table for detailed record views
     *
     * @var FinnaRecordView
     */
    protected $recordView;

    /**
     * Record view institution data table for detailed record views
     *
     * @var FinnaRecordViewInstView
     */
    protected $recordViewInstView;

    /**
     * Record view record data table for detailed record views
     *
     * @var FinnaRecordViewRecord
     */
    protected $recordViewRecord;

    /**
     * Record view record format data table for detailed record views
     *
     * @var FinnaRecordViewRecordFormat
     */
    protected $recordViewRecordFormat;

    /**
     * Record view record usage rights data table for detailed record views
     *
     * @var FinnaRecordViewRecordRights
     */
    protected $recordViewRecordRights;

    /**
     * Formats cache for detailed record views
     *
     * @var array
     */
    protected $formatCache = [];

    /**
     * Usage rights cache for detailed record views
     *
     * @var array
     */
    protected $usageRightsCache = [];

    /**
     * Cached record view record for detailed record views
     *
     * @var FinnaRecordViewRecord
     */
    protected $cachedViewRecord = null;

    /**
     * Cached record view inst/view record for detailed record views
     *
     * @var FinnaRecordViewInstView
     */
    protected $cachedViewInstView = null;

    /**
     * Constructor
     *
     * @param FinnaSessionStats           $sessionTable           Session table
     * @param FinnaPageViewStats          $pageViewTable          Page view table
     * @param FinnaRecordStats            $recordTable            Record view table
     * @param FinnaRecordStatsLog         $recordLogTable         Record view log
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
        FinnaSessionStats $sessionTable,
        FinnaPageViewStats $pageViewTable,
        FinnaRecordStats $recordTable,
        FinnaRecordStatsLog $recordLogTable,
        FinnaRecordView $recordView,
        FinnaRecordViewInstView $recordViewInstView,
        FinnaRecordViewRecord $recordViewRecord,
        FinnaRecordViewRecordFormat $recordViewRecordFormat,
        FinnaRecordViewRecordRights $recordViewRecordRights
    ) {
        $this->sessionTable = $sessionTable;
        $this->pageViewTable = $pageViewTable;
        $this->recordTable = $recordTable;
        $this->recordLogTable = $recordLogTable;
        $this->recordView = $recordView;
        $this->recordViewInstView = $recordViewInstView;
        $this->recordViewRecord = $recordViewRecord;
        $this->recordViewRecordFormat = $recordViewRecordFormat;
        $this->recordViewRecordRights = $recordViewRecordRights;
    }

    /**
     * Add a new session to statistics
     *
     * @param string $institution Institution code
     * @param string $view        View subpath (empty string for default view)
     * @param int    $type        Request type bitmap
     * @param array  $session     Session data
     *
     * @return void
     */
    public function addNewSession(
        string $institution,
        string $view,
        int $type,
        array $session
    ): void {
        $date = date('Y-m-d');
        $crawler = $type;
        $params = compact('institution', 'view', 'crawler', 'date');
        $this->addNewSessionEntry($params);
    }

    /**
     * Add a page view to statistics
     *
     * @param string $institution Institution code
     * @param string $view        View subpath (empty string for default view)
     * @param int    $type        Request type bitmap
     * @param string $controller  Controller
     * @param string $action      Action
     *
     * @return void
     */
    public function addPageView(
        string $institution,
        string $view,
        int $type,
        string $controller,
        string $action
    ): void {
        $date = date('Y-m-d');
        $crawler = $type;
        $params = compact(
            'institution',
            'view',
            'crawler',
            'controller',
            'action',
            'date'
        );
        $this->addPageViewEntry($params);
    }

    /**
     * Add a record view to statistics
     *
     * @param string $institution Institution code
     * @param string $view        View subpath (empty string for default view)
     * @param int    $type        Request type bitmap
     * @param string $backend     Backend ID
     * @param string $source      Record source
     * @param string $recordId    Record ID
     * @param array  $formats     Record formats
     * @param array  $rights      Record usage rights
     * @param int    $online      Whether the record is available online (0 = no,
     * 1 = yes, 2 = freely)
     *
     * @return void
     */
    public function addRecordView(
        string $institution,
        string $view,
        int $type,
        string $backend,
        string $source,
        string $recordId,
        array $formats,
        array $rights,
        int $online
    ): void {
        $date = date('Y-m-d');

        // Summary log:
        $crawler = $type;
        $params = compact(
            'institution',
            'view',
            'crawler',
            'date',
            'backend',
            'source'
        );
        $this->addRecordViewEntry($params);

        // Record log:
        $params['record_id'] = $recordId;
        $params['formats'] = implode('|', $formats);
        $params['usage_rights'] = implode('|', $rights);
        $params['online'] = $online;
        $this->addRecordLogEntry($params);
    }

    /**
     * Add a session entry
     *
     * @param array $params Row identification params
     *
     * @return void
     */
    public function addNewSessionEntry(array $params): void
    {
        $this->processAdd($this->sessionTable, $params);
    }

    /**
     * Add a page view entry
     *
     * @param array $params Row identification params
     *
     * @return void
     */
    public function addPageViewEntry(array $params): void
    {
        $this->processAdd($this->pageViewTable, $params);
    }

    /**
     * Add a record view entry
     *
     * @param array $params Row identification params
     *
     * @return void
     */
    public function addRecordViewEntry(array $params): void
    {
        $this->processAdd($this->recordTable, $params);
    }

    /**
     * Add a detailed record log entry
     *
     * @param array $params Row identification params
     *
     * @return void
     */
    public function addRecordLogEntry(array $params): void
    {
        $this->processAdd($this->recordLogTable, $params);
    }

    /**
     * Add a detailed record view entry from a log entry
     *
     * Note: This is a relatively slow and complex function and should only be
     * executed from a batch processing utility
     *
     * @param array $logEntry Log entry
     *
     * @return void
     */
    public function addDetailedRecordViewEntry(array $logEntry): void
    {
        $formatId = $this->getRecordViewFormatId($logEntry['formats']);
        $rightsId
            = $this->getRecordViewUsageRightsId($logEntry['usage_rights']);
        $recordId = $this->getRecordViewRecordId(
            $logEntry + ['format_id' => $formatId, 'usage_rights_id' => $rightsId]
        );
        $viewFields = [
            'inst_view_id' => $this->getRecordViewInstViewId($logEntry),
            'crawler' => $logEntry['crawler'],
            'date' => $logEntry['date'],
            'record_id' => $recordId,
        ];

        $rowsAffected = $this->recordView->update(
            [
                'count' => new \Laminas\Db\Sql\Literal(
                    'count + ' . ($logEntry['count'] ?? '1')
                ),
            ],
            $viewFields,
        );
        if (0 === $rowsAffected) {
            $hit = $this->recordView->createRow();
            $hit->populate($viewFields);
            $hit->count = $logEntry['count'] ?? 1;
            $hit->save();
        }
    }

    /**
     * Add or update a statistics table entry
     *
     * @param AbstractTableGateway $table  Table
     * @param array                $params Row identification params
     *
     * @return void
     *
     * @throws \Exception
     */
    protected function processAdd(AbstractTableGateway $table, array $params): void
    {
        $exception = null;
        for ($try = 1; $try < 5; $try++) {
            try {
                // Try update first, insert then:
                if (!$this->incrementCount($table, $params)) {
                    try {
                        $row = $table->createRow();
                        $row->populate($params, false);
                        $row->save();
                    } catch (\RuntimeException $e) {
                        // Did someone else just add the row? Try update again!
                        $this->incrementCount($table, $params);
                    }
                }
                break;
            } catch (\Exception $e) {
                $exception = $e;
                usleep(1000);
            }
        }
        if (null !== $exception) {
            throw $exception;
        }
    }

    /**
     * Increment count for an existing row
     *
     * @param AbstractTableGateway $table Table
     * @param array                $where Fields for row identification
     *
     * @return bool Whether a row was updated
     */
    protected function incrementCount(
        AbstractTableGateway $table,
        array $where
    ): bool {
        $rowsAffected = $table->update(
            [
                'count' => new \Laminas\Db\Sql\Literal('count + 1'),
            ],
            $where
        );
        return 0 !== $rowsAffected;
    }

    /**
     * Get id of a FinnaRecordViewRecord for a log entry
     *
     * @param array $logEntry Log entry
     *
     * @return int
     */
    protected function getRecordViewInstViewId(array $logEntry): int
    {
        if (
            null === $this->cachedViewInstView
            || $this->cachedViewInstView->institution !== $logEntry['institution']
            || $this->cachedViewInstView->view !== $logEntry['view']
        ) {
            $this->cachedViewInstView
                = $this->recordViewInstView->getByLogEntry($logEntry);
        }
        return $this->cachedViewInstView->id;
    }

    /**
     * Get id of a FinnaRecordViewRecord for a log entry
     *
     * @param array $logEntry Log entry
     *
     * @return int
     */
    protected function getRecordViewRecordId(array $logEntry): int
    {
        if (
            null === $this->cachedViewRecord
            || $this->cachedViewRecord->backend !== $logEntry['backend']
            || $this->cachedViewRecord->source !== $logEntry['source']
            || $this->cachedViewRecord->record_id !== $logEntry['record_id']
        ) {
            $this->cachedViewRecord
                = $this->recordViewRecord->getByLogEntry($logEntry);
        }
        return $this->cachedViewRecord->id;
    }

    /**
     * Get id of a FinnaRecordViewRecordFormat for a formats string
     *
     * @param string $formats Formats
     *
     * @return int
     */
    protected function getRecordViewFormatId(string $formats): int
    {
        if (!isset($this->formatCache[$formats])) {
            $this->formatCache[$formats]
                = $this->recordViewRecordFormat->getByFormat($formats)->id;
        }
        return $this->formatCache[$formats];
    }

    /**
     * Get id of a FinnaRecordViewRecordRights for a usage rights string
     *
     * @param string $usageRights Usage rights
     *
     * @return int
     */
    protected function getRecordViewUsageRightsId(string $usageRights): int
    {
        if (!isset($this->usageRightsCache[$usageRights])) {
            $this->usageRightsCache[$usageRights]
                = $this->recordViewRecordRights->getByUsageRights($usageRights)->id;
        }
        return $this->usageRightsCache[$usageRights];
    }
}
