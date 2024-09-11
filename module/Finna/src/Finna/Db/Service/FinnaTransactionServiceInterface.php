<?php

/**
 * Database service interface for FinnaTransaction.
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

use Finna\Db\Entity\FinnaFeeEntityInterface;
use Finna\Db\Entity\FinnaTransactionEntityInterface;
use VuFind\Db\Service\DbServiceInterface;

/**
 * Database service interface for FinnaTransaction.
 *
 * @category VuFind
 * @package  Database
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:database_gateways Wiki
 */
interface FinnaTransactionServiceInterface extends DbServiceInterface
{
    /**
     * Delete a transaction entity.
     *
     * @param FinnaTransactionEntityInterface|int $transactionOrId FinnaTransaction entity object or ID to delete
     *
     * @return void
     */
    public function deleteTransaction(FinnaTransactionEntityInterface|int $transactionOrId): void;

    /**
     * Retrieve a transaction object.
     *
     * @param int $id Numeric ID for existing transaction.
     *
     * @return FinnaTransactionEntityInterface
     * @throws RecordMissingException
     */
    public function getTransactionById(int $id): FinnaTransactionEntityInterface;

    /**
     * Get fines associated with a transaction
     *
     * @param FinnaTransactionEntityInterface $transaction Transaction
     *
     * @return FinnaFeeEntityInterface[]
     */
    public function getFines(FinnaTransactionEntityInterface $transaction): array;

    /**
     * Get IDs from fines associated with a transaction
     *
     * @param FinnaTransactionEntityInterface $transaction Transaction
     *
     * @return array
     */
    public function getFineIds(FinnaTransactionEntityInterface $transaction): array;

    /**
     * Get last paid transaction for a patron
     *
     * @param string $catUsername Patron's catalog username
     *
     * @return ?FinnaTransactionEntityInterface
     */
    public function getLastPaidTransactionForPatron(string $catUsername): ?FinnaTransactionEntityInterface;

    /**
     * Check if payment is in progress for the patron.
     *
     * @param string $catUsername Patron's catalog username
     *
     * @return bool
     */
    public function isPaymentInProgressForPatron(string $catUsername): bool;

    /**
     * Get transaction by identifier
     *
     * @param string $transactionIdentifier Transaction Identifier.
     *
     * @return ?FinnaTransactionEntityInterface
     */
    public function getTransactionByIdentifier($transactionIdentifier): ?FinnaTransactionEntityInterface;

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
    ): ?FinnaTransactionEntityInterface;

    /**
     * Get paid transactions whose registration failed.
     *
     * @param int $minimumPaidAge How old a paid transaction must be (in seconds) for
     * it to be considered failed
     *
     * @return FinnaTransactionEntityInterface[]
     */
    public function getFailedTransactions($minimumPaidAge = 120): array;

    /**
     * Get unresolved transactions for reporting.
     *
     * @param int $interval Minimum hours since last report was sent.
     *
     * @return FinnaTransactionEntityInterface[]
     */
    public function getUnresolvedTransactions($interval): array;
}
