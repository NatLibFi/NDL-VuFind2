<?php

/**
 * Online payment handler support trait
 *
 * Dependencies:
 *   $this->ils
 *   $this->logError
 *   $this->logException
 *   $this->logPaymentInfo
 *   OnlinePaymentEventLogTrait
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
 * @package  OnlinePayment
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:controllers Wiki
 */

namespace Finna\OnlinePayment;

use Finna\Db\Row\Transaction as TransactionRow;
use Finna\Db\Row\User as UserRow;

/**
 * Online payment handler support trait.
 *
 * @category VuFind
 * @package  OnlinePayment
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:controllers Wiki
 */
trait OnlinePaymentHandlerTrait
{
    /**
     * Mark fees paid for the given transaction and patron
     *
     * @param array          $patron Patron information
     * @param TransactionRow $t      Transaction
     *
     * @return bool
     */
    protected function markFeesAsPaidForPatron(array $patron, TransactionRow $t): bool
    {
        // Check that registration is not already in progress (i.e. registration started within 120 seconds)
        if ($t->isRegistrationInProgress()) {
            $this->logPaymentInfo(
                '    Transaction ' . $t->transaction_id . ' already being registered since ' . $t->registration_started
            );
            $this->addTransactionEvent($t->id, 'Transaction already being registered');
            return false;
        }

        $t->setRegistrationStarted();
        $this->addTransactionEvent($t->id, 'Started registration with the ILS');

        $paymentConfig = $this->ils->getConfig('onlinePayment', $patron);
        $fineIds = $t->getFineIds();

        if (
            ($paymentConfig['exactBalanceRequired'] ?? true)
            || !empty($paymentConfig['creditUnsupported'])
        ) {
            try {
                $fines = $this->ils->getMyFines($patron);
                // Filter by fines selected for the transaction if fine_id field is
                // available:
                $finesAmount = $this->ils->getOnlinePaymentDetails(
                    $patron,
                    $fines,
                    $fineIds ?: null
                );
            } catch (\Exception $e) {
                $this->logException($e);
                return false;
            }

            // Check that payable sum has not been updated
            $exact = $paymentConfig['exactBalanceRequired'] ?? true;
            $noCredit = $exact || !empty($paymentConfig['creditUnsupported']);
            if (
                $finesAmount['payable'] && !empty($finesAmount['amount'])
                && (($exact && $t->amount != $finesAmount['amount'])
                || ($noCredit && $t->amount > $finesAmount['amount']))
            ) {
                // Payable sum updated. Skip registration and inform user
                // that payment processing has been delayed.
                $this->logError(
                    'Transaction ' . $t->transaction_id . ': payable sum updated.'
                    . ' Paid amount: ' . $t->amount . ', payable: '
                    . print_r($finesAmount, true)
                );
                $t->setFinesUpdated();
                $this->addTransactionEvent($t->id, 'Registration with the ILS failed: fines updated');
                return false;
            }
        }

        try {
            $this->logPaymentInfo('Transaction ' . $t->transaction_id . ': start marking fees as paid.');
            $res = $this->ils->markFeesAsPaid(
                $patron,
                $t->amount,
                $t->transaction_id,
                $t->id,
                ($paymentConfig['selectFines'] ?? false) ? $fineIds : null
            );
            $this->logPaymentInfo(
                'Transaction ' . $t->transaction_id . ': done marking fees as paid, result: '
                . var_export($res, true)
            );
            if (true !== $res) {
                $this->logError(
                    'Payment registration error (patron ' . $patron['id'] . '): '
                    . 'markFeesAsPaid failed: ' . ($res ?: 'no error information')
                );
                if ('fines_updated' === $res) {
                    $t->setFinesUpdated();
                    $this->addTransactionEvent($t->id, 'Registration with the ILS failed: fines updated');
                } else {
                    $t->setRegistrationFailed('Failed to mark fees paid: ' . ($res ?: 'no error information'));
                    $this->addTransactionEvent(
                        $t->id,
                        'Registration with the ILS failed: ' . ($res ?: 'no error information')
                    );
                }
                return false;
            }
            $t->setRegistered();
            $this->logPaymentInfo("Registration of transaction {$t->transaction_id} successful");
            $this->addTransactionEvent($t->id, 'Successfully registered with the ILS');
        } catch (\Exception $e) {
            $this->logError(
                'Payment registration error (patron ' . $patron['id'] . '): ' . $e->getMessage()
            );
            $this->logException($e);
            $t->setRegistrationFailed($e->getMessage());
            $this->addTransactionEvent($t->id, 'Registration with the ILS failed', ['error' => $e->getMessage()]);
            return false;
        }
        return true;
    }

    /**
     * Find patron for a transaction
     *
     * @param TransactionRow $t    Transaction
     * @param UserRow        $user User
     *
     * @return array Patron, or null on failure
     */
    protected function getPatronForTransaction(TransactionRow $t, &$user): ?array
    {
        if (!($user = $this->userTable->getById($t->user_id))) {
            return null;
        }

        // Check if user's current credentials match (typical case):
        if (
            mb_strtolower($user->cat_username, 'UTF-8') === mb_strtolower($t->cat_username, 'UTF-8')
            && ($patron = $this->ils->patronLogin($user->cat_username, $user->getCatPassword()))
        ) {
            // Success!
            return $patron;
        }

        // Check for a matching library card:
        $cards = $user->getLibraryCardsByUserName($t->cat_username);

        // Make sure to try all cards with a matching user name:
        foreach ($cards as $card) {
            // Read the card with a separate call to decrypt password:
            if (!($card = $user->getLibraryCard($card->id))) {
                continue;
            }
            try {
                if ($patron = $this->ils->patronLogin($card->cat_username, $card->cat_password)) {
                    // Success!
                    return $patron;
                }
            } catch (\Exception $e) {
                $this->logError('Patron login error: ' . $e->getMessage());
                $this->logException($e);
            }
        }
        return null;
    }
}
