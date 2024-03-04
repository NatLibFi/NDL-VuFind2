<?php

/**
 * Abstract base class for online payment handlers.
 *
 * PHP version 8
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
 * @package  AJAX
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @author   Samuli Sillanpää <samuli.sillanpaa@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development Wiki
 */

namespace Finna\AjaxHandler;

use Finna\Db\Row\Transaction as TransactionRow;
use Finna\Db\Table\Transaction as TransactionTable;
use Finna\Db\Table\TransactionEventLog;
use Finna\OnlinePayment\OnlinePayment;
use Finna\OnlinePayment\Receipt;
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
abstract class AbstractOnlinePaymentAction extends \VuFind\AjaxHandler\AbstractBase implements
    \Laminas\Log\LoggerAwareInterface
{
    use \VuFind\Log\LoggerAwareTrait;
    use \Finna\OnlinePayment\OnlinePaymentEventLogTrait;
    use \Finna\OnlinePayment\OnlinePaymentHandlerTrait;

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
     * Data source configuration
     *
     * @var array
     */
    protected $dataSourceConfig;

    /**
     * Receipt
     *
     * @var \Finna\OnlinePayment\Receipt
     */
    protected $receipt;

    /**
     * Transaction event log table
     *
     * @var TransactionEventLog
     */
    protected $eventLogTable;

    /**
     * Constructor
     *
     * @param SessionSettings     $ss  Session settings
     * @param Connection          $ils ILS connection
     * @param TransactionTable    $tt  Transaction table
     * @param UserTable           $ut  User table
     * @param OnlinePayment       $op  Online payment manager
     * @param SessionContainer    $os  Online payment session
     * @param array               $ds  Data source configuration
     * @param Receipt             $rcp Receipt
     * @param TransactionEventLog $elt Transaction event log table
     */
    public function __construct(
        SessionSettings $ss,
        Connection $ils,
        TransactionTable $tt,
        UserTable $ut,
        OnlinePayment $op,
        SessionContainer $os,
        array $ds,
        Receipt $rcp,
        TransactionEventLog $elt
    ) {
        $this->sessionSettings = $ss;
        $this->ils = $ils;
        $this->transactionTable = $tt;
        $this->userTable = $ut;
        $this->onlinePayment = $op;
        $this->onlinePaymentSession = $os;
        $this->dataSourceConfig = $ds;
        $this->receipt = $rcp;
        $this->eventLogTable = $elt;
    }

    /**
     * Mark fees paid for the given transaction
     *
     * @param TransactionRow $t Transaction
     *
     * @return bool
     */
    protected function markFeesAsPaidForTransaction(TransactionRow $t): bool
    {
        if (!($patron = $this->getPatronForTransaction($t, $user))) {
            $this->logError(
                'Error processing transaction id ' . $t->id
                . ': patronLogin error (cat_username: ' . $t->cat_username
                . ', user id: ' . $t->user_id . ')'
            );

            $t->setRegistrationFailed('patron login error');
            $this->addTransactionEvent($t->id, 'Patron login failed');
            return false;
        }

        $result = $this->markFeesAsPaidForPatron($patron, $t);
        if ($result) {
            $this->onlinePaymentSession->paymentOk = true;
        }
        return $result;
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

    /**
     * Log a payment info message
     *
     * @param string $msg Message
     *
     * @return void
     */
    protected function logPaymentInfo(string $msg): void
    {
        $this->logWarning($msg);
    }
}
