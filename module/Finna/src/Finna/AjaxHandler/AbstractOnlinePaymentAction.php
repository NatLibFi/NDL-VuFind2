<?php
/**
 * Abstract base class for online payment handlers.
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
 * @package  AJAX
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @author   Samuli Sillanpää <samuli.sillanpaa@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development Wiki
 */
namespace Finna\AjaxHandler;

use Finna\Db\Row\Transaction as TransactionRow;
use Finna\Db\Table\Transaction as TransactionTable;
use Finna\OnlinePayment\OnlinePayment;
use Laminas\Session\Container as SessionContainer;
use VuFind\Db\Table\User as UserTable;
use VuFind\ILS\Connection;
use VuFind\Session\Settings as SessionSettings;

/**
 * Abstract base class for online payment handlers.
 *
 * @category VuFind
 * @package  AJAX
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @author   Samuli Sillanpää <samuli.sillanpaa@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development Wiki
 */
abstract class AbstractOnlinePaymentAction extends \VuFind\AjaxHandler\AbstractBase
    implements \Laminas\Log\LoggerAwareInterface
{
    use \VuFind\Log\LoggerAwareTrait;

    /**
     * ILS connection
     *
     * @var Connection
     */
    protected $ils;

    /**
     * Transaction table
     *
     * @var TransactionTable
     */
    protected $transactionTable;

    /**
     * User table
     *
     * @var UserTable
     */
    protected $userTable;

    /**
     * Online payment manager
     *
     * @var OnlinePayment
     */
    protected $onlinePayment;

    /**
     * Online payment session
     *
     * @var SessionContainer
     */
    protected $onlinePaymentSession;

    /**
     * Constructor
     *
     * @param SessionSettings  $ss  Session settings
     * @param Connection       $ils ILS connection
     * @param TransactionTable $tt  Transaction table
     * @param UserTable        $ut  User table
     * @param OnlinePayment    $op  Online payment manager
     * @param SessionContainer $os  Online payment session
     */
    public function __construct(
        SessionSettings $ss,
        Connection $ils,
        TransactionTable $tt,
        UserTable $ut,
        OnlinePayment $op,
        SessionContainer $os
    ) {
        $this->sessionSettings = $ss;
        $this->ils = $ils;
        $this->transactionTable = $tt;
        $this->userTable = $ut;
        $this->onlinePayment = $op;
        $this->onlinePaymentSession = $os;
    }

    /**
     * Mark fees paid for the given transaction
     *
     * @param TransactionRow $t Transaction
     *
     * @return array
     */
    protected function markFeesAsPaid(TransactionRow $t): array
    {
        $userCard = null;
        $user = $this->userTable->getById($t->user_id);
        foreach ($user->getLibraryCards() as $card) {
            if ($card->cat_username === $t->cat_username) {
                $userCard = $user->getLibraryCard($card->id);
                break;
            }
        }

        if (!$userCard) {
            $this->logError(
                'Error processing transaction id ' . $t->id
                . ': user card not found (cat_username: ' . $t->cat_username
                . ', user id: ' . $t->user_id . ')'
            );
            return ['success' => false];
        }

        $patron = null;
        try {
            $patron = $this->ils->patronLogin(
                $userCard->cat_username,
                $userCard->cat_password
            );
        } catch (\Exception $e) {
            $this->logException($e);
        }

        if (!$patron) {
            $this->logError(
                'Error processing transaction id ' . $t->id
                . ': patronLogin error (cat_username: ' . $t->cat_username
                . ', user id: ' . $t->user_id . ')'
            );

            $t->setRegistrationFailed('patron login error');
            return ['success' => false];
        }

        $paymentConfig = $this->ils->getConfig('onlinePayment', $patron);
        if (($paymentConfig['exactBalanceRequired'] ?? true)
            || !empty($paymentConfig['creditUnsupported'])
        ) {
            try {
                $fines = $this->ils->getMyFines($patron);
                $finesAmount = $this->ils->getOnlinePayableAmount($patron, $fines);
            } catch (\Exception $e) {
                $this->logException($e);
                return ['success' => false];
            }

            // Check that payable sum has not been updated
            $exact = $paymentConfig['exactBalanceRequired'] ?? true;
            $noCredit = $exact || !empty($paymentConfig['creditUnsupported']);
            if ($finesAmount['payable'] && !empty($finesAmount['amount'])
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
                return [
                    'success' => false,
                    'msg' => 'online_payment_registration_failed'
                ];
            }
        }

        try {
            $this->ils->markFeesAsPaid(
                $patron,
                $t->amount,
                $t->transaction_id,
                $t->id
            );
            $t->setRegistered();
            $this->onlinePaymentSession->paymentOk = true;
        } catch (\Exception $e) {
            $this->logError(
                'Payment registration error (patron ' . $patron['id'] . '): '
                . $e->getMessage()
            );
            $this->logException($e);

            $t->setRegistrationFailed($e->getMessage());
            return ['success' => false, 'msg' => $e->getMessage()];
        }
        return ['success' => true];
    }

    /**
     * Return online payment handler.
     *
     * @param string $driver Patron MultiBackend ILS source
     *
     * @return mixed \Finna\OnlinePayment\BaseHandler or false on failure.
     */
    protected function getOnlinePaymentHandler($driver)
    {
        if (!$this->onlinePayment->isEnabled($driver)) {
            return false;
        }

        try {
            return $this->onlinePayment->getHandler($driver);
        } catch (\Exception $e) {
            $this->logError(
                "Error retrieving online payment handler for driver $driver"
                . ' (' . $e->getMessage() . ')'
            );
            return false;
        }
    }

    /**
     * Log an exception
     *
     * @param \Exception $exception Exception to log
     *
     * @return void
     */
    public function logException(\Exception $exception): void
    {
        if ($this->logger instanceof \VuFind\Log\Logger) {
            $this->logger
                ->logException($exception, new \Laminas\Stdlib\Parameters());
        }
    }
}
