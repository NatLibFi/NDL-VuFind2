<?php

/**
 * Database service for Finna transactions.
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

use Finna\Db\Entity\FinnaTransactionEntityInterface;
use Finna\Db\Type\FinnaTransactionStatus;
use VuFind\Db\Service\AbstractDbService;
use VuFind\Db\Table\DbTableAwareInterface;
use VuFind\Db\Table\DbTableAwareTrait;
use VuFind\Exception\RecordMissing as RecordMissingException;

use function sprintf;

/**
 * Database service for Finna transactions.
 *
 * @category VuFind
 * @package  Database
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:database_gateways Wiki
 */
class FinnaTransactionService extends AbstractDbService implements
    DbTableAwareInterface,
    FinnaTransactionServiceInterface
{
    use DbTableAwareTrait;

    /**
     * Create a FinnaTransaction entity object.
     *
     * @return FinnaTransactionEntityInterface
     */
    public function createEntity(): FinnaTransactionEntityInterface
    {
        $transaction = $this->getDbTable('Transaction')->createRow();
        $transaction->created = date('Y-m-d H:i:s');
        $transaction->complete = 0;
        $transaction->status = 'started';
        return $transaction;
    }

    /**
     * Delete a transaction entity.
     *
     * @param FinnaTransactionEntityInterface|int $transactionOrId FinnaTransaction entity object or ID to delete
     *
     * @return void
     */
    public function deleteTransaction(FinnaTransactionEntityInterface|int $transactionOrId): void
    {
        $transactionId = $transactionOrId instanceof FinnaTransactionEntityInterface
            ? $transactionOrId->getId() : $transactionOrId;
        $this->getDbTable('Transaction')->delete(['id' => $transactionId]);
    }

    /**
     * Retrieve a transaction object.
     *
     * @param int $id Numeric ID for existing transaction.
     *
     * @return FinnaTransactionEntityInterface
     * @throws RecordMissingException
     */
    public function getTransactionById(int $id): FinnaTransactionEntityInterface
    {
        $result = $this->getDbTable('Transaction')->select(['id' => $id])->current();
        if (empty($result)) {
            throw new RecordMissingException('Cannot load transaction ' . $id);
        }
        return $result;
    }

    /**
     * Get fines associated with a transaction
     *
     * @param FinnaTransactionEntityInterface $transaction Transaction
     *
     * @return FinnaFeeEntityInterface[]
     */
    public function getFines(FinnaTransactionEntityInterface $transaction): array
    {
        $feeTable = $this->getDbTable('Fee');
        return iterator_to_array($feeTable->select(['transaction_id' => $transaction->getId()]));
    }

    /**
     * Get IDs from fines associated with a transaction
     *
     * @param FinnaTransactionEntityInterface $transaction Transaction
     *
     * @return array
     */
    public function getFineIds(FinnaTransactionEntityInterface $transaction): array
    {
        $fineIds = [];
        foreach ($this->getFines($transaction) as $fee) {
            if (!empty($fee['fine_id'])) {
                $fineIds[] = $fee['fine_id'];
            }
        }
        return $fineIds;
    }

    /**
     * Get last paid transaction for a patron
     *
     * @param string $catUsername Patron's catalog username
     *
     * @return ?FinnaTransactionEntityInterface
     */
    public function getLastPaidTransactionForPatron(string $catUsername): ?FinnaTransactionEntityInterface
    {
        $statuses = [
            FinnaTransactionStatus::Complete->value,
            FinnaTransactionStatus::Paid->value,
            FinnaTransactionStatus::RegistrationFailed->value,
            FinnaTransactionStatus::RegistrationExpired->value,
            FinnaTransactionStatus::RegistrationResolved->value,
            FinnaTransactionStatus::FinesUpdated->value,
        ];

        $callback = function (\Laminas\Db\Sql\Select $select) use (
            $catUsername,
            $statuses
        ) {
            $select->where->equalTo('cat_username', $catUsername);
            $select->where('complete in (' . implode(',', $statuses) . ')');
            $select->order('paid desc');
        };

        return $this->getDbTable('Transaction')->select($callback)->current();
    }

    /**
     * Check if payment is in progress for the patron.
     *
     * @param string $catUsername Patron's catalog username
     *
     * @return bool
     */
    public function isPaymentInProgressForPatron(string $catUsername): bool
    {
        $statuses = [
            FinnaTransactionStatus::Paid->value,
            FinnaTransactionStatus::RegistrationFailed->value,
            FinnaTransactionStatus::RegistrationExpired->value,
            FinnaTransactionStatus::FinesUpdated->value,
        ];

        $callback = function ($select) use ($catUsername, $statuses) {
            $select->where->equalTo('cat_username', $catUsername);
            $select->where('complete in (' . implode(',', $statuses) . ')');
        };

        return $this->getDbTable('Transaction')->select($callback)->count() ? true : false;
    }

    /**
     * Get transaction by identifier
     *
     * @param string $transactionIdentifier Transaction Identifier.
     *
     * @return ?FinnaTransactionEntityInterface
     */
    public function getTransactionByIdentifier($transactionIdentifier): ?FinnaTransactionEntityInterface
    {
        return $this->getDbTable('Transaction')->select(['transaction_id' => $transactionIdentifier])->current();
    }

    /**
     * Check if a transaction is started for the patron, but not progressed further.
     *
     * @param string $catUsername            Patron's catalog username
     * @param int    $transactionMaxDuration Max duration for a transaction in minutes
     *
     * @return ?FinnaTransactionEntityInterface
     */
    public function getStartedTransactionForPatron(
        string $catUsername,
        int $transactionMaxDuration
    ): ?FinnaTransactionEntityInterface {
        $callback = function ($select) use ($catUsername, $transactionMaxDuration) {
            $select->where->equalTo('cat_username', $catUsername);
            $select->where->equalTo('complete', FinnaTransactionStatus::InProgress->value);
            $select->where(
                "NOW() < DATE_ADD(created, INTERVAL $transactionMaxDuration MINUTE)"
            );
        };

        return $this->getDbTable('Transaction')->select($callback)->current();
    }

    /**
     * Get paid transactions whose registration failed.
     *
     * @param int $minimumPaidAge How old a paid transaction must be (in seconds) for
     * it to be considered failed
     *
     * @return FinnaTransactionEntityInterface[]
     */
    public function getFailedTransactions($minimumPaidAge = 120): array
    {
        $callback = function ($select) use ($minimumPaidAge) {
            $select->where->nest
                ->equalTo('complete', FinnaTransactionStatus::RegistrationFailed->value)
                ->greaterThan('paid', '2000-01-01 00:00:00')
                ->unnest
                ->or->nest
                ->equalTo('complete', FinnaTransactionStatus::Paid->value)
                ->greaterThan('paid', '2000-01-01 00:00:00')
                ->lessThan(
                    'paid',
                    date('Y-m-d H:i:s', time() - $minimumPaidAge)
                );

            $select->order('user_id');
        };

        return iterator_to_array($this->getDbTable('Transaction')->select($callback));
    }

    /**
     * Get unresolved transactions for reporting.
     *
     * @param int $interval Minimum hours since last report was sent.
     *
     * @return FinnaTransactionEntityInterface[] transactions
     */
    public function getUnresolvedTransactions($interval): array
    {
        $callback = function ($select) use (
            $interval
        ) {
            $select->where->in(
                'complete',
                [
                    FinnaTransactionStatus::FinesUpdated->value,
                    FinnaTransactionStatus::RegistrationExpired->value,
                ]
            );
            $select->where->greaterThan('paid', '2000-01-01 00:00:00');
            $select->where(
                sprintf(
                    'NOW() > DATE_ADD(reported, INTERVAL %u HOUR)',
                    $interval
                )
            );
            $select->order('user_id');
        };

        $items = [];
        foreach ($this->getDbTable('Transaction')->select($callback) as $t) {
            $items[] = $t;
        }
        return $items;
    }
}
