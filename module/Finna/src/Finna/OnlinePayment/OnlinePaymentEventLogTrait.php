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

use Finna\Db\Entity\FinnaTransactionEntityInterface;
use Finna\Db\Service\FinnaTransactionEventLogServiceInterface;
use VuFind\Controller\AbstractBase;

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
     * Transaction event log service
     *
     * @var ?FinnaTransactionEventLogServiceInterface
     */
    protected $eventLogService = null;

    /**
     * Add an event log entry for a transaction
     *
     * @param FinnaTransactionEntityInterface $transaction Transaction
     * @param string                          $status      Status message
     * @param array                           $data        Additional data
     *
     * @return void
     */
    protected function addTransactionEvent(
        FinnaTransactionEntityInterface $transaction,
        string $status,
        array $data = []
    ): void {
        if (null === $this->eventLogService) {
            if ($this instanceof AbstractBase) {
                $this->eventLogService = $this->getDbService(FinnaTransactionEventLogServiceInterface::class);
            } else {
                throw new \Exception('Event log service not set');
            }
        }
        $data += ['source' => static::class];
        $this->eventLogService->addEvent($transaction, $status, $data);
    }
}
