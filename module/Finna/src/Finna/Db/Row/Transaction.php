<?php
/**
 * Row definition for online payment transaction
 *
 * PHP version 7
 *
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
 * @package  Db_Table
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org   Main Site
 */
namespace Finna\Db\Row;

use Finna\Db\Table\Transaction as TransactionTable;

/**
 * Row definition for online payment transaction
 *
 * @category VuFind
 * @package  Db_Table
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org   Main Site
 */
class Transaction extends \VuFind\Db\Row\RowGateway
{
    /**
     * Constructor
     *
     * @param \Laminas\Db\Adapter\Adapter $adapter Database adapter
     */
    public function __construct($adapter)
    {
        parent::__construct('id', 'finna_transaction', $adapter);
    }

    /**
     * Check if the transaction is in progress
     *
     * @return bool
     */
    public function isInProgress()
    {
        return in_array(
            $this->complete,
            [
                TransactionTable::STATUS_PROGRESS,
                TransactionTable::STATUS_REGISTRATION_FAILED
            ]
        );
    }

    /**
     * Check if the transaction is registered (fees marked paid in the ILS)
     *
     * @return bool
     */
    public function isRegistered()
    {
        return $this->complete === TransactionTable::STATUS_COMPLETE;
    }

    /**
     * Set transaction canceled
     *
     * @return void
     */
    public function setCanceled(): void
    {
        $this->complete = TransactionTable::STATUS_CANCELED;
        $this->status = 'cancel';
        $this->save();
    }

    /**
     * Check if the transaction is paid and needs registration with the ILS
     *
     * @return bool
     */
    public function needsRegistration()
    {
        return in_array(
            $this->complete,
            [
                TransactionTable::STATUS_PAID,
                TransactionTable::STATUS_REGISTRATION_FAILED
            ]
        );
    }

    /**
     * Set transaction paid
     *
     * @param int $timestamp Optional payment unix timestamp
     *
     * @return void
     */
    public function setPaid(int $timestamp = null): void
    {
        $this->paid = date('Y-m-d H:i:s', $timestamp);
        $this->complete = TransactionTable::STATUS_PAID;
        $this->status = 'paid';
        $this->save();
    }

    /**
     * Set transaction registered
     *
     * @return void
     */
    public function setRegistered(): void
    {
        $this->registered = date('Y-m-d H:i:s');
        $this->complete = TransactionTable::STATUS_COMPLETE;
        $this->status = 'register_ok';
        $this->save();
    }

    /**
     * Set transaction status to "registration failed"
     *
     * @param string $msg Message
     *
     * @return void
     */
    public function setRegistrationFailed(string $msg): void
    {
        $this->complete = TransactionTable::STATUS_REGISTRATION_FAILED;
        $this->status = $msg;
        $this->save();
    }

    /**
     * Set transaction reported date and status to "registration expired"
     *
     * @return void
     */
    public function setReportedAndExpired(): void
    {
        $this->complete = TransactionTable::STATUS_REGISTRATION_EXPIRED;
        $this->reported = date('Y-m-d H:i:s');
        $this->save();
    }

    /**
     * Set transaction status to "fines updated"
     *
     * @return void
     */
    public function setFinesUpdated(): void
    {
        $this->complete = TransactionTable::STATUS_FINES_UPDATED;
        $this->status = 'fines_updated';
        $this->save();
    }
}
