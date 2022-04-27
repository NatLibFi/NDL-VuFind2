<?php
/**
 * Database driver for statistics
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
 * @package  Statistics
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development Wiki
 */
namespace Finna\Statistics\Driver;

use Finna\Db\Table\FinnaPageViewStats;
use Finna\Db\Table\FinnaRecordStats;
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
     * Table for record statistics
     *
     * @var FinnaRecordStats
     */
    protected $recordTable;

    /**
     * Constructor
     *
     * @param FinnaSessionStats  $sessionTable  Session table
     * @param FinnaPageViewStats $pageViewTable Page view table
     * @param FinnaRecordStats   $recordTable   Record view table
     */
    public function __construct(
        FinnaSessionStats $sessionTable,
        FinnaPageViewStats $pageViewTable,
        FinnaRecordStats $recordTable
    ) {
        $this->sessionTable = $sessionTable;
        $this->pageViewTable = $pageViewTable;
        $this->recordTable = $recordTable;
    }

    /**
     * Add a new session to statistics
     *
     * @param string $institution Institution code
     * @param string $view        View code (empty string for default view)
     * @param array  $session     Session data
     *
     * @return void
     */
    public function addNewSession(
        string $institution,
        string $view,
        array $session
    ): void {
        $date = date('Y-m-d');
        $params = compact('institution', 'view', 'date');
        $this->processAdd($this->sessionTable, $params);
    }

    /**
     * Add a page view to statistics
     *
     * @param string $institution Institution code
     * @param string $view        View code (empty string for default view)
     * @param string $controller  Controller
     * @param string $action      Action
     *
     * @return void
     */
    public function addPageView(
        string $institution,
        string $view,
        string $controller,
        string $action
    ): void {
        $date = date('Y-m-d');
        $params = compact('institution', 'view', 'controller', 'action', 'date');
        $this->processAdd($this->pageViewTable, $params);
    }

    /**
     * Add a record view to statistics
     *
     * @param string $institution Institution code
     * @param string $view        View code (empty string for default view)
     * @param string $backend     Backend ID
     * @param string $source      Record source
     * @param string $recordId    Record ID
     *
     * @return void
     */
    public function addRecordView(
        string $institution,
        string $view,
        string $backend,
        string $source,
        string $recordId
    ): void {
        $date = date('Y-m-d');
        $params = compact('institution', 'view', 'date', 'backend', 'source');
        $this->processAdd($this->recordTable, $params);
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
                'count' => new \Laminas\Db\Sql\Literal('count + 1')
            ],
            $where
        );
        return 0 !== $rowsAffected;
    }
}
