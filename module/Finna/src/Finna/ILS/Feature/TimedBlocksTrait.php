<?php

/**
 * Timed blocks trait
 *
 * PHP version 8
 *
 * Copyright (C) The National Library of Finland 2025
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
 * @package  ILS_Drivers
 * @author   Jaro Ravila <jaro.ravila@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:ils_drivers Wiki
 */

namespace Finna\ILS\Feature;

/**
 * Timed method blocks trait
 *
 * @category VuFind
 * @package  ILS_Drivers
 * @author   Jaro Ravila <jaro.ravila@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:ils_drivers Wiki
 */
trait TimedBlocksTrait
{
    /**
     * Check if given method is blocked currently
     *
     * @param string $methodName method to check
     *
     * @return bool
     */
    public function checkTimedBlock($methodName): bool
    {
        $methodBlocks = [];
        foreach ($this->config['TimedBlocks'] ?? [] as $key => $value) {
            [$method, $setting] = explode(':', $key, 2);
            $methodBlocks[$method][$setting] = $value;
        }

        if (!isset($methodBlocks[$methodName])) {
            return false;
        }

        $now = new \DateTime();
        $methodBlock = $methodBlocks[$methodName];
        if (isset($methodBlock['startDate']) && isset($methodBlock['endDate'])) {
            $startDate = \DateTime::createFromFormat('d.m.Y', $methodBlock['startDate']);
            $endDate = \DateTime::createFromFormat('d.m.Y', $methodBlock['endDate']);
            if ($startDate && $endDate && $now >= $startDate && $now <= $endDate) {
                return true;
            }
        }

        if (isset($methodBlock['recurringStart']) && isset($methodBlock['recurringEnd'])) {
            $nowTime = \DateTime::createFromFormat('H:i', $now->format('H:i'));
            $startTime = \DateTime::createFromFormat('H:i', $methodBlock['recurringStart']);
            $endTime = \DateTime::createFromFormat('H:i', $methodBlock['recurringEnd']);
            if ($startTime && $endTime) {
                if ($startTime < $endTime) {
                    return $nowTime >= $startTime
                    && $nowTime <= $endTime;
                } else {
                    return $nowTime >= $startTime
                    || $nowTime <= $endTime;
                }
            }
        }

        return false;
    }
}
