<?php

/**
 * Online payment event log support trait
 *
 * PHP version 8
 *
 * Copyright (C) The National Library of Finland 2023.
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
 * @package  OnlinePayment
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:controllers Wiki
 */

namespace Finna\OnlinePayment;

use Finna\Db\Table\TransactionEventLog;

/**
 * Online payment event log support trait.
 *
 * @category VuFind
 * @package  OnlinePayment
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:controllers Wiki
 */
trait OnlinePaymentEventLogTrait
{
    /**
     * Transaction event log table
     *
     * @var TransactionEventLog
     */
    protected $eventLogTable = null;

    /**
     * Add an event log entry for a transaction
     *
     * @param int    $id     Transaction ID
     * @param string $status Status message
     * @param array  $data   Additional data
     *
     * @return void
     */
    protected function addTransactionEvent(int $id, string $status, array $data = []): void
    {
        if (null === $this->eventLogTable) {
            throw new \Exception('Event log table not set');
        }
        $data += ['source' => static::class];
        $this->eventLogTable->addEvent($id, $status, $data);
    }
}
