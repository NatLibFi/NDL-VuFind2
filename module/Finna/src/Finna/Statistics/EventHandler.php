<?php
/**
 * Statistics event handler
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
namespace Finna\Statistics;

use Finna\Statistics\Driver\DriverInterface;
use VuFind\RecordDriver\AbstractBase as AbstractRecord;

/**
 * Statistics event handler
 *
 * @category VuFind
 * @package  Statistics
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org Main Site
 */
class EventHandler
{
    /**
     * Institution code
     *
     * @param string
     */
    protected $institution;

    /**
     * View code
     *
     * @param string
     */
    protected $view;

    /**
     * Storage driver
     *
     * @var ?DriverInterface
     */
    protected $driver;

    /**
     * Constructor
     *
     * Note that this must be called before any of the events to be handled is
     *
     * @param string      $institution Institution code
     * @param string      $view        View code
     * @param ?BaseDriver $driver      Statistics storage driver
     */
    public function __construct(
        string $institution,
        string $view,
        ?DriverInterface $driver
    ) {
        $this->institution = $institution;
        $this->view = $view;
        $this->driver = $driver;
    }

    /**
     * Session start event
     *
     * @param array $params Session data
     *
     * @return void
     */
    public function sessionStart(array $params): void
    {
        if ($this->driver) {
            $this->driver->addNewSession($this->institution, $this->view, $params);
        }
    }

    /**
     * Page view event
     *
     * @param string $controller Controller
     * @param string $action     Action
     *
     * @return void
     */
    public function pageView(string $controller, string $action): void
    {
        if ($this->driver) {
            $this->driver->addPageView(
                $this->institution,
                $this->view,
                $controller,
                $action
            );
        }
    }

    /**
     * Record view event
     *
     * @param AbstractRecord $driver Record driver
     *
     * @return void
     */
    public function recordView(AbstractRecord $driver): void
    {
        if ($this->driver) {
            if (!($source = $driver->tryMethod('getDatasource'))) {
                [$source] = explode('.', $driver->getUniqueID(), 2);
            }

            $this->driver->addRecordView(
                $this->institution,
                $this->view,
                $driver->getSourceIdentifier(),
                $source,
                $driver->getUniqueID(),
                $driver->tryMethod('getFormats') ?? [],
                $driver->tryMethod('getUsageRights') ?? [],
            );
        }
    }
}
