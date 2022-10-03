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
     * Holds all counters
     *
     * @var array
     */
    protected $counters = [];

    /**
     * Increase the counter of given id by amount
     *
     * @param string $id     Identifier of counter to increment
     * @param int    $amount Amount to increment
     *
     * @return $int Value of the counter after increment
     */
    public function increment(string $id, int $amount = 1): int
    {
        $this->create($id);
        return $this->counters[$id] = $this->counters[$id] + $amount;
    }

    /**
     * Decrease the counter of given id by amount
     *
     * @param string $id     Identifier of counter to decrement
     * @param int    $amount Amount to decrement
     *
     * @return $int Value of the counter after decrement
     */
    public function decrement(string $id, int $amount = 1): int
    {
        $this->create($id);
        return $this->counters[$id] = $this->counters[$id] - $amount;
    }

    /**
     * Create a counter and set the starting value
     *
     * @param string $id    Identifier of counter to create
     * @param string $start Starting value
     *
     * @return void
     */
    public function create(string $id, int $start = 0): void
    {
        if (!isset($this->counters[$id])) {
            $this->counters[$id] = $start;
        }
    }

    /**
     * Remove a counter
     *
     * @param string $id Identifier of counter to remove
     *
     * @return void
     */
    public function remove(string $id): void
    {
        unset($this->counters[$id]);
    }

    /**
     * Get the value of a counter
     *
     * @param string $id Identifier of counter to get
     *
     * @return $int Value of the counter
     */
    public function get(string $id): int
    {
        $this->create($id);
        return $this->counters[$id];
    }

    /**
     * Set a counter to certain value
     *
     * @param string $id    Identifier of counter to remove
     * @param string $value Value to set. If omitted, 0 will be used.
     *
     * @return void
     */
    public function set(string $id, int $value = 0): void
    {
        $this->create($id, $value);
        $this->counters[$id] = $value;
    }
}