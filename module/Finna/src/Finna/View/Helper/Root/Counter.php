<?php
/**
 * Counter view helper
 *
 * PHP version 7
 *
 * Copyright (C) Villanova University 2010.
 * Copyright (C) The National Library of Finland 2015-2022.
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
 * @package  View_Helpers
 * @author   Juha Luoma <juha.luoma@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/vufind2:developer_manual Wiki
 */
namespace Finna\View\Helper\Root;

/**
 * Counter view helper
 *
 * @category VuFind
 * @package  View_Helpers
 * @author   Juha Luoma <juha.luoma@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/vufind2:developer_manual Wiki
 */
class Counter extends \Laminas\View\Helper\AbstractHelper
{
    /**
     * Holds all counters.
     *
     * @var array
     */
    protected $counters = [];

    /**
     * Current counter id.
     *
     * @var string
     */
    protected $currentId = '';

    /**
     * Create a counter and set the starting value.
     *
     * @param string $id    Identifier of counter to create.
     * @param int    $start Starting value. Default 0.
     *
     * @return void
     */
    public function __invoke(string $id, int $start = 0): Counter
    {
        $this->currentId = $id;
        $this->create($start);
        return $this;
    }

    /**
     * Increase the counter of given id by amount.
     *
     * @param int $amount Amount to increment.
     *
     * @return int The increment result.
     */
    public function increment(
        int $amount = 1
    ): int {
        return $this->counters[$this->currentId] += $amount;
    }

    /**
     * Decrease the counter of given id by amount.
     *
     * @param int $amount Amount to decrement.
     *
     * @return int The decrement result.
     */
    public function decrement(
        int $amount = 1
    ): int {
        return $this->counters[$this->currentId] -= $amount;
    }

    /**
     * Create a counter and set the starting value.
     *
     * @param int $start Starting value.
     *
     * @return void
     */
    protected function create(int $start = 0): void
    {
        if (!isset($this->counters[$this->currentId])) {
            $this->counters[$this->currentId] = $start;
        }
    }

    /**
     * Remove the current counter.
     *
     * @return void
     */
    public function remove(): void
    {
        unset($this->counters[$this->currentId]);
    }

    /**
     * Get the value of a counter.
     *
     * @return $int Value of the counter.
     */
    public function get(): int
    {
        return $this->counters[$this->currentId];
    }

    /**
     * Set a counter to certain value.
     *
     * @param string $value Value to set. If omitted, 0 will be used.
     *
     * @return Counter this
     */
    public function set(int $value = 0): Counter
    {
        $this->counters[$this->currentId] = $value;
        return $this;
    }
}
