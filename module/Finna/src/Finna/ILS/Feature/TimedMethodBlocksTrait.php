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

use Laminas\Mvc\Plugin\FlashMessenger\FlashMessenger;

/**
 * Timed method blocks trait
 *
 * @category VuFind
 * @package  ILS_Drivers
 * @author   Jaro Ravila <jaro.ravila@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:ils_drivers Wiki
 */
trait TimedMethodBlocksTrait
{
    /**
     * Flashmessenger
     *
     * @var FlashMessenger
     */
    protected ?FlashMessenger $fm = null;

    /**
     * Check if given method is blocked currently
     *
     * @param string $methodName method to check
     *
     * @return bool
     */
    public function methodIsBlocked(string $methodName): bool
    {
        $methodBlocks = [];
        foreach ($this->config['TimedBlocks'] ?? [] as $key => $value) {
            [$method, $setting] = explode(':', $key, 2);
            $methodBlocks[$method][$setting] = $value;
        }

        if (!isset($methodBlocks[$methodName])) {
            return false;
        }

        $now = strtotime('now');
        $methodBlock = $methodBlocks[$methodName];
        if (isset($methodBlock['startDate']) && isset($methodBlock['endDate'])) {
            $startDate = strtotime($methodBlock['startDate']);
            $endDate = strtotime($methodBlock['endDate']);
            $startHours = !empty(explode(' ', $methodBlock['startDate'], 2)[1]);
            $endHours = !empty(explode(' ', $methodBlock['endDate'], 2)[1]);
            if ($startDate && $endDate && $now >= $startDate && $now <= $endDate) {
                if (isset($methodBlock['message'])) {
                    $startDateStr = $startHours
                        ? $this->dateConverter->convertToDisplayDateAndTime('U', $startDate)
                        : $this->dateConverter->convertToDisplayDate('U', $startDate);
                    $endDateStr = $endHours
                        ? $this->dateConverter->convertToDisplayDateAndTime('U', $endDate)
                        : $this->dateConverter->convertToDisplayDate('U', $endDate);
                    $this->addBlockMessage($methodBlock['message'], $startDateStr, $endDateStr);
                }
                return true;
            }
        }

        if (isset($methodBlock['recurringStart']) && isset($methodBlock['recurringEnd'])) {
            $startTime = strtotime($methodBlock['recurringStart']);
            $endTime = strtotime($methodBlock['recurringEnd']);
            if ($startTime && $endTime) {
                $blocked = false;
                if ($startTime < $endTime) {
                    if ($now >= $startTime && $now <= $endTime) {
                        $blocked = true;
                    }
                } else {
                    if ($now >= $startTime || $now <= $endTime) {
                        $blocked = true;
                    }
                }
                if ($blocked) {
                    if (isset($methodBlock['message'])) {
                        $startTimeStr = $this->dateConverter->convertToDisplayTime('U', $startTime);
                        $endTimeStr = $this->dateConverter->convertToDisplayTime('U', $endTime);
                        $this->addBlockMessage($methodBlock['message'], $startTimeStr, $endTimeStr);
                    }
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Add block message
     *
     * @param string $message   Translation key for the message
     * @param string $startDate Start date of the block
     * @param string $endDate   End date of the block
     *
     * @return void
     */
    protected function addBlockMessage(string $message, string $startDate, string $endDate): void
    {
        if (null === $this->fm) {
            $this->fm = new FlashMessenger();
        }
        $this->fm->addWarningMessage(
            [
                'msg' => $message,
                'tokens' => [
                    '%%startDate%%' => $startDate,
                    '%%endDate%%' => $endDate,
                ],
            ]
        );
    }
}
