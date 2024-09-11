<?php

/**
 * Database service for Finna statistics.
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

use Finna\Db\Entity\FinnaPageViewStatsEntityInterface;
use Finna\Db\Entity\FinnaRecordStatsLogEntityInterface;
use Finna\Db\Entity\FinnaRecordViewInstitutionViewEntityInterface;
use Finna\Db\Entity\FinnaRecordViewRecordEntityInterface;
use Finna\Db\Entity\FinnaRecordViewRecordFormatEntityInterface;
use Finna\Db\Entity\FinnaRecordViewRecordRightsEntityInterface;
use Finna\Db\Entity\FinnaSessionStatsEntityInterface;
use Finna\Db\Table\FinnaRecordViewInstView;
use Laminas\Db\TableGateway\AbstractTableGateway;
use VuFind\Db\Service\AbstractDbService;
use VuFind\Db\Table\DbTableAwareInterface;
use VuFind\Db\Table\DbTableAwareTrait;

/**
 * Database service for Finna statistics.
 *
 * @category VuFind
 * @package  Database
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:database_gateways Wiki
 */
class FinnaStatisticsService extends AbstractDbService implements
    DbTableAwareInterface,
    FinnaStatisticsServiceInterface
{
    use DbTableAwareTrait;

    /**
     * Formats cache for detailed record views
     *
     * @var FinnaRecordViewRecordFormatEntityInterface[]
     */
    protected $formatCache = [];

    /**
     * Usage rights cache for detailed record views
     *
     * @var FinnaRecordViewRecordRightsEntityInterface[]
     */
    protected $usageRightsCache = [];

    /**
     * Cache for a view record
     *
     * @var ?FinnaRecordViewRecordEntityInterface
     */
    protected ?FinnaRecordViewRecordEntityInterface $cachedViewRecord = null;

    /**
     * Cache for an institution+view
     *
     * @var ?FinnaRecordViewInstitutionViewEntityInterface
     */
    protected ?FinnaRecordViewInstitutionViewEntityInterface $cachedViewInstView = null;

    /**
     * Create a new session stats entity
     *
     * @return FinnaSessionStatsEntityInterface
     */
    public function createSessionEntity(): FinnaSessionStatsEntityInterface
    {
        return $this->getDbTable(\Finna\Db\Table\FinnaSessionStats::class)->createRow();
    }

    /**
     * Create a new page view entity
     *
     * @return FinnaPageViewStatsEntityInterface
     */
    public function createPageViewEntity(): FinnaPageViewStatsEntityInterface
    {
        return $this->getDbTable(\Finna\Db\Table\FinnaPageViewStats::class)->createRow();
    }

    /**
     * Create a new record stats log entity
     *
     * @return FinnaRecordStatsLogEntityInterface
     */
    public function createRecordStatsLogEntity(): FinnaRecordStatsLogEntityInterface
    {
        return $this->getDbTable(\Finna\Db\Table\FinnaRecordStatsLog::class)->createRow();
    }

    /**
     * Get a batch of log entries to process from finna_record_stats_log table
     *
     * @param int $batchSize Number of records to retrieve
     *
     * @return FinnaRecordStatsLogEntityInterface[]
     */
    public function getRecordStatsLogEntriesToProcess(int $batchSize): array
    {
        $callback = function ($select) use ($batchSize) {
            $select->where->lessThan('date', date('Y-m-d'));
            $select->limit($batchSize);
        };
        $table = $this->getDbTable(\Finna\Db\Table\FinnaRecordStatsLog::class);
        return iterator_to_array($table->select($callback));
    }

    /**
     * Delete a record stats log entry
     *
     * @param FinnaRecordStatsLogEntityInterface $entry Log entry
     *
     * @return void
     */
    public function deleteRecordStatsLogEntry(FinnaRecordStatsLogEntityInterface $entry): void
    {
        $this->getDbTable(\Finna\Db\Table\FinnaRecordStatsLog::class)->delete(
            [
                'institution' => $entry->getInstitution(),
                'view' => $entry->getView(),
                'crawler' => $entry->getType()->value,
                'date' => $entry->getDate()->format('Y-m-d'),
                'backend' => $entry->getBackend(),
                'source' => $entry->getSource(),
                'record_id' => $entry->getRecordId(),
            ]
        );
    }

    /**
     * Add a new session entry
     *
     * @param FinnaSessionStatsEntityInterface $session Session
     *
     * @return void
     */
    public function addSession(FinnaSessionStatsEntityInterface $session): void
    {
        $params = [
            'institution' => $session->getInstitution(),
            'view' => $session->getView(),
            'crawler' => $session->getType()->value,
            'date' => $session->getDate()->format('Y-m-d'),
        ];

        $this->processAdd($this->getDbTable(\Finna\Db\Table\FinnaSessionStats::class), $params);
    }

    /**
     * Add a page view
     *
     * @param FinnaPageViewStatsEntityInterface $pageView Page view
     *
     * @return void
     */
    public function addPageView(FinnaPageViewStatsEntityInterface $pageView): void
    {
        $params = [
            'institution' => $pageView->getInstitution(),
            'view' => $pageView->getView(),
            'crawler' => $pageView->getType()->value,
            'date' => $pageView->getDate()->format('Y-m-d'),
            'controller' => $pageView->getController(),
            'action' => $pageView->getAction(),
        ];

        $this->processAdd($this->getDbTable(\Finna\Db\Table\FinnaPageViewStats::class), $params);
    }

    /**
     * Add a record view entry from a log entry
     *
     * @param FinnaRecordStatsLogEntityInterface $logEntry Log entry
     *
     * @return void
     */
    public function addRecordView(FinnaRecordStatsLogEntityInterface $logEntry): void
    {
        $params = [
            'inst_view_id' => $this->getRecordViewInstViewByLogEntry($logEntry)->getId(),
            'crawler' => $logEntry->getType()->value,
            'date' => $logEntry->getDate()->format('Y-m-d'),
            'record_id' => $this->getRecordViewRecordByLogEntry($logEntry)->getId(),
        ];

        $this->processAdd($this->getDbTable(\Finna\Db\Table\FinnaRecordView::class), $params);
    }

    /**
     * Add a record stats log entry (a detailed entry for processing later via addDetailedRecordView)
     *
     * @param FinnaRecordStatsLogEntityInterface $logEntry Log entry
     *
     * @return void
     */
    public function addRecordStatsLogEntry(FinnaRecordStatsLogEntityInterface $logEntry): void
    {
        $params = [
            'institution' => $logEntry->getInstitution(),
            'view' => $logEntry->getView(),
            'crawler' => $logEntry->getType()->value,
            'date' => $logEntry->getDate()->format('Y-m-d'),
            'backend' => $logEntry->getBackend(),
            'source' => $logEntry->getSource(),
            'record_id' => $logEntry->getRecordId(),
            'formats' => $logEntry->getFormats(),
            'usage_rights' => $logEntry->getUsageRights(),
            'online'  => $logEntry->getOnline() ? 1 : 0,
            'extra_metadata' => $logEntry->getExtraMetadata(),
        ];

        $this->processAdd($this->getDbTable(\Finna\Db\Table\FinnaRecordStatsLog::class), $params);
    }

    /**
     * Add a detailed record view entry from a log entry
     *
     * Note: This is a relatively slow and complex function and should only be
     * executed from a batch processing utility
     *
     * @param FinnaRecordStatsLogEntityInterface $logEntry Log entry
     *
     * @return void
     */
    public function addDetailedRecordView(FinnaRecordStatsLogEntityInterface $logEntry): void
    {
        $record = $this->getRecordViewRecordByLogEntry($logEntry);
        $params = [
            'inst_view_id' => $this->getRecordViewInstViewByLogEntry($logEntry)->getId(),
            'crawler' => $logEntry->getType()->value,
            'date' => $logEntry->getDate()->format('Y-m-d'),
            'record_id' => $record->getId(),
        ];

        $this->processAdd($this->getDbTable(\Finna\Db\Table\FinnaRecordView::class), $params);
    }

    /**
     * Get a record view format entity by id
     *
     * @param int $id Id
     *
     * @return FinnaRecordViewRecordFormatEntityInterface
     */
    public function getRecordViewRecordFormatById(int $id): FinnaRecordViewRecordFormatEntityInterface
    {
        return $this->getDbTable(\Finna\Db\Table\FinnaRecordViewRecordFormat::class)->select(compact('id'))->current();
    }

    /**
     * Get a record view format entity by id
     *
     * @param int $id Id
     *
     * @return FinnaRecordViewRecordRightsEntityInterface
     */
    public function getRecordViewRecordUsageRightsById(int $id): FinnaRecordViewRecordRightsEntityInterface
    {
        return $this->getDbTable(\Finna\Db\Table\FinnaRecordViewRecordRights::class)->select(compact('id'))->current();
    }

    /**
     * Get a record view record by log entry
     *
     * @param FinnaRecordStatsLogEntityInterface $logEntry Log entry
     *
     * @return FinnaRecordViewRecordEntityInterface
     */
    public function getRecordViewRecordByLogEntry(
        FinnaRecordStatsLogEntityInterface $logEntry
    ): FinnaRecordViewRecordEntityInterface {
        if (
            null !== $this->cachedViewRecord
            && $this->cachedViewRecord->getBackend() === $logEntry->getBackend()
            && $this->cachedViewRecord->getSource() === $logEntry->getSource()
            && $this->cachedViewRecord->getRecordId() === $logEntry->getRecordId()
        ) {
            return $this->cachedViewRecord;
        }

        $table = $this->getDbTable(\Finna\Db\Table\FinnaRecordViewRecord::class);
        $record = $table->select(
            [
                'backend' => $logEntry->getBackend(),
                'source' => $logEntry->getSource(),
                'record_id' => $logEntry->getRecordId(),
            ]
        )->current();
        if (!$record) {
            $record = $table->createRow();
        }
        $record
            ->setBackend($logEntry->getBackend())
            ->setSource($logEntry->getSource())
            ->setRecordId($logEntry->getRecordId())
            ->setFormat($this->getRecordViewRecordFormatFromString($logEntry->getFormats()))
            ->setUsageRights($this->getRecordViewRecordRightsFromString($logEntry->getUsageRights()))
            ->setOnline($logEntry->getOnline())
            ->setExtraMetadata($logEntry->getExtraMetadata());
        $record->save();
        $this->cachedViewRecord = $record;
        return $record;
    }

    /**
     * Get record view record format entity from string
     *
     * @param string $format Format
     *
     * @return FinnaRecordViewRecordFormatEntityInterface
     */
    protected function getRecordViewRecordFormatFromString(string $format): FinnaRecordViewRecordFormatEntityInterface
    {
        if (!isset($this->formatCache[$format])) {
            $this->formatCache[$format]
                = $this->getDbTable(\Finna\Db\Table\FinnaRecordViewRecordFormat::class)->getByFormat($format);
        }
        return $this->formatCache[$format];
    }

    /**
     * Get record view record rights entity from string
     *
     * @param string $rights Rights
     *
     * @return FinnaRecordViewRecordRightsEntityInterface
     */
    protected function getRecordViewRecordRightsFromString(string $rights): FinnaRecordViewRecordRightsEntityInterface
    {
        if (!isset($this->usageRightsCache[$rights])) {
            $this->usageRightsCache[$rights]
                = $this->getDbTable(\Finna\Db\Table\FinnaRecordViewRecordRights::class)->getByUsageRights($rights);
        }
        return $this->usageRightsCache[$rights];
    }

    /**
     * Get FinnaRecordViewInstitutionView for a log entry
     *
     * @param FinnaRecordStatsLogEntityInterface $logEntry Log entry
     *
     * @return FinnaRecordViewInstitutionViewEntityInterface
     */
    protected function getRecordViewInstViewByLogEntry(
        FinnaRecordStatsLogEntityInterface $logEntry
    ): FinnaRecordViewInstitutionViewEntityInterface {
        if (
            null !== $this->cachedViewInstView
            && $this->cachedViewInstView->getInstitution() === $logEntry->getInstitution()
            && $this->cachedViewInstView->getView() === $logEntry->getView()
        ) {
            return $this->cachedViewInstView;
        }

        $table = $this->getDbTable(FinnaRecordViewInstView::class);
        $record = $table->select(
            [
                'institution' => $logEntry->getInstitution(),
                'view' => $logEntry->getView(),
            ]
        )->current();
        if (!$record) {
            $record = $table->createRow();
            $record->setInstitution($logEntry->getInstitution());
            $record->setView($logEntry->getView());
            $record->save();
        }
        $this->cachedViewInstView = $record;
        return $record;

        return $this->cachedViewInstView;
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
}
