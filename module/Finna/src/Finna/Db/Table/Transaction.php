<?php

/**
 * Table Definition for online payment transaction
 *
 * PHP version 7
 *
 * Copyright (C) The National Library of Finland 2015-2023.
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
 * @package  Db_Table
 * @author   Samuli Sillanpää <samuli.sillanpaa@helsinki.fi>
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org   Main Site
 */

namespace Finna\Db\Table;

use Laminas\Db\Adapter\Adapter;
use VuFind\Db\Row\RowGateway;
use VuFind\Db\Table\PluginManager;

/**
 * Table Definition for online payment transaction
 *
 * @category VuFind
 * @package  Db_Table
 * @author   Samuli Sillanpää <samuli.sillanpaa@helsinki.fi>
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org   Main Site
 */
class Transaction extends \VuFind\Db\Table\Gateway
{
    public const STATUS_PROGRESS              = 0;
    public const STATUS_COMPLETE              = 1;

    public const STATUS_CANCELED              = 2;
    public const STATUS_PAID                  = 3;
    public const STATUS_PAYMENT_FAILED        = 4;

    public const STATUS_REGISTRATION_FAILED   = 5;
    public const STATUS_REGISTRATION_EXPIRED  = 6;
    public const STATUS_REGISTRATION_RESOLVED = 7;

    public const STATUS_FINES_UPDATED         = 8;

    /**
     * Constructor
     *
     * @param Adapter       $adapter Database adapter
     * @param PluginManager $tm      Table manager
     * @param array         $cfg     Laminas configuration
     * @param RowGateway    $rowObj  Row prototype object (null for default)
     * @param string        $table   Name of database table to interface with
     */
    public function __construct(
        Adapter $adapter,
        PluginManager $tm,
        $cfg,
        RowGateway $rowObj = null,
        $table = 'finna_transaction'
    ) {
        parent::__construct($adapter, $tm, $cfg, $rowObj, $table);
    }

    /**
     * Create transaction.
     *
     * @param string $id             Transaction ID
     * @param string $driver         Patron MultiBackend ILS source.
     * @param string $userId         User ID
     * @param string $patronId       Patron catalog username
     * @param int    $amount         Amount (excluding transaction fee)
     * @param int    $transactionFee Transaction fee
     * @param string $currency       Currency
     *
     * @return Finna\Db\Row\Transaction
     */
    public function createTransaction(
        $id,
        $driver,
        $userId,
        $patronId,
        $amount,
        $transactionFee,
        $currency
    ) {
        $t = $this->createRow();
        $t->transaction_id = $id;
        $t->driver = $driver;
        $t->user_id = $userId;
        $t->amount = $amount;
        $t->transaction_fee = $transactionFee;
        $t->currency = $currency;
        $t->created = date("Y-m-d H:i:s");
        $t->complete = 0;
        $t->status = 'started';
        $t->cat_username = $patronId;
        $t->save();
        return $t;
    }

    /**
     * Check if payment is in progress for the patron.
     *
     * Payment is not permitted if:
     *   - patron has a transaction in progress and translation maximum duration
     *     has not been exceeded
     *   - patron has a paid transaction that has not been registered as paid
     *     to the ILS
     *
     * @param string $patronId Patron's Catalog username (barcode).
     *
     * @return bool
     */
    public function isPaymentInProgress(string $patronId): bool
    {
        $statuses = [
            self::STATUS_PAID,
            self::STATUS_REGISTRATION_FAILED,
            self::STATUS_REGISTRATION_EXPIRED,
            self::STATUS_FINES_UPDATED,
        ];

        $callback = function ($select) use ($patronId, $statuses) {
            $select->where->equalTo('cat_username', $patronId);
            $select->where('complete in (' . implode(',', $statuses) . ')');
        };

        return $this->select($callback)->count() ? true : false;
    }

    /**
     * Get paid transactions whose registration failed.
     *
     * @param int $minimumPaidAge How old a paid transaction must be (in seconds) for
     * it to be considered failed
     *
     * @return array transactions
     */
    public function getFailedTransactions($minimumPaidAge = 120)
    {
        $callback = function ($select) use ($minimumPaidAge) {
            $select->where->nest
                ->equalTo('complete', self::STATUS_REGISTRATION_FAILED)
                ->greaterThan('paid', '2000-01-01 00:00:00')
                ->unnest
                ->or->nest
                ->equalTo('complete', self::STATUS_PAID)
                ->greaterThan('paid', '2000-01-01 00:00:00')
                ->lessThan(
                    'paid',
                    date('Y-m-d H:i:s', time() - $minimumPaidAge)
                );

            $select->order('user_id');
        };

        $items = [];
        foreach ($this->select($callback) as $t) {
            $items[] = $t;
        }
        return $items;
    }

    /**
     * Get unresolved transactions for reporting.
     *
     * @param int $interval Minimum hours since last report was sent.
     *
     * @return array transactions
     */
    public function getUnresolvedTransactions($interval)
    {
        $updatedStatus = self::STATUS_FINES_UPDATED;
        $expiredStatus = self::STATUS_REGISTRATION_EXPIRED;

        $callback = function ($select) use (
            $updatedStatus,
            $expiredStatus,
            $interval
        ) {
            $select->where->in(
                'complete',
                [$updatedStatus, $expiredStatus]
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
        foreach ($this->select($callback) as $t) {
            $items[] = $t;
        }
        return $items;
    }

    /**
     * Get transaction.
     *
     * @param string $transactionId Transaction ID.
     *
     * @return \Finna\Db\Row\Transaction transaction or false on error
     */
    public function getTransaction($transactionId)
    {
        $row = $this->select(['transaction_id' => $transactionId])->current();
        return empty($row) ? false : $row;
    }
}
