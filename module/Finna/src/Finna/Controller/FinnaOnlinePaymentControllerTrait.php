<?php
/**
 * Online payment controller trait.
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
 * @package  Controller
 * @author   Leszek Manicki <leszek.z.manicki@helsinki.fi>
 * @author   Samuli Sillanpää <samuli.sillanpaa@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:controllers Wiki
 */
namespace Finna\Controller;

use Laminas\Stdlib\Parameters;

/**
 * Online payment controller trait.
 *
 * @category VuFind
 * @package  Controller
 * @author   Leszek Manicki <leszek.z.manicki@helsinki.fi>
 * @author   Samuli Sillanpää <samuli.sillanpaa@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:controllers Wiki
 */
trait FinnaOnlinePaymentControllerTrait
{
    use \VuFind\Log\LoggerAwareTrait;

    /**
     * Checks if the given list of fines is identical to the listing
     * preserved in the session variable.
     *
     * @param array $patron Patron.
     * @param int   $amount Total amount to pay without fees
     *
     * @return boolean updated
     */
    protected function checkIfFinesUpdated($patron, $amount)
    {
        $session = $this->getOnlinePaymentSession();

        if (!$session) {
            $this->logError(
                'PaymentSessionError: Session was empty for: '
                . json_encode($patron) . ' and amount was '
                . json_encode($amount)
            );
            return true;
        }

        $finesUpdated = false;
        $sessionId = $this->generateFingerprint($patron);

        if ($session->sessionId !== $sessionId) {
            $this->logError(
                'PaymentSessionError: Session id does not match for: '
                . json_encode($patron) . '. Old id / new id hashes = '
                . $session->sessionId . ' and ' . $sessionId
            );
            $finesUpdated = true;
        }
        if ($session->amount !== $amount) {
            $this->logError(
                'PaymentSessionError: Payment amount updated: '
                . $session->amount . ' and ' . $amount
            );
            $finesUpdated = true;
        }
        return $finesUpdated;
    }

    /**
     * Utility function for calculating a fingerprint for a object.
     *
     * @param object $data Object
     *
     * @return string fingerprint
     */
    protected function generateFingerprint($data)
    {
        return md5(json_encode($data));
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
        $onlinePayment = $this->serviceLocator
            ->get(\Finna\OnlinePayment\OnlinePayment::class);
        if (!$onlinePayment->isEnabled($driver)) {
            return false;
        }

        try {
            return $onlinePayment->getHandler($driver);
        } catch (\Exception $e) {
            $this->handleError(
                "Error retrieving online payment handler for driver $driver"
                . ' (' . $e->getMessage() . ')'
            );
            return false;
        }
    }

    /**
     * Get session for storing payment data.
     *
     * @return SessionContainer
     */
    protected function getOnlinePaymentSession()
    {
        return $this->serviceLocator->get('Finna\OnlinePayment\Session');
    }

    /**
     * Support for handling online payments.
     *
     * @param array     $patron Patron
     * @param array     $fines  Listing of fines
     * @param ViewModel $view   View
     *
     * @return void
     */
    protected function handleOnlinePayment($patron, $fines, $view)
    {
        $view->onlinePaymentEnabled = false;
        if (!($paymentHandler = $this->getOnlinePaymentHandler($patron['source']))) {
            return;
        }

        $session = $this->getOnlinePaymentSession();
        $catalog = $this->getILS();

        // Check if online payment configuration exists for the ILS driver
        $paymentConfig = $catalog->getConfig('onlinePayment', $patron);
        if (empty($paymentConfig)) {
            return;
        }

        // Check if payment handler is configured in datasources.ini
        $onlinePayment = $this->serviceLocator
            ->get(\Finna\OnlinePayment\OnlinePayment::class);
        if (!$onlinePayment->isEnabled($patron['source'])) {
            return;
        }

        // Check if online payment is enabled for the ILS driver
        if (!$catalog->checkFunction('markFeesAsPaid', compact('patron'))) {
            return;
        }

        // Check that mandatory settings exist
        if (!isset($paymentConfig['currency'])) {
            $this->handleError(
                "Mandatory setting 'currency' missing from ILS driver for"
                . " '{$patron['source']}'"
            );
            return false;
        }

        $payableOnline = $catalog->getOnlinePayableAmount($patron, $fines);

        $callback = function ($fine) {
            return $fine['payableOnline'];
        };
        $payableFines = array_filter($fines, $callback);

        $view->onlinePayment = true;
        $view->paymentHandler = $onlinePayment->getHandlerName($patron['source']);
        $view->transactionFee = $paymentConfig['transactionFee'] ?? 0;
        $view->minimumFee = $paymentConfig['minimumFee'] ?? 0;
        $view->payableOnline = $payableOnline['amount'];
        $view->payableTotal = $payableOnline['amount'] + $view->transactionFee;
        $view->payableOnlineCnt = count($payableFines);
        $view->nonPayableFines = count($fines) != count($payableFines);
        $view->registerPayment = false;

        $trTable = $this->getTable('transaction');
        $transactionIdParam = 'finna_payment_id';
        $pay = $this->formWasSubmitted('pay-confirm');
        if ($pay && $session && $payableOnline
            && $payableOnline['payable'] && $payableOnline['amount']
        ) {
            // Payment started, check that fee list has not been updated
            if ((($paymentConfig['exactBalanceRequired'] ?? true)
                || !empty($paymentConfig['creditUnsupported']))
                && $this->checkIfFinesUpdated($patron, $payableOnline['amount'])
            ) {
                // Fines updated, redirect and show updated list.
                $this->flashMessenger()
                    ->addErrorMessage('online_payment_fines_changed');
                header("Location: " . $this->getServerUrl('myresearch-fines'));
                exit();
            }
            $returnUrl = $this->getServerUrl('myresearch-fines');
            $notifyUrl = $this->getServerUrl('home') . 'AJAX/onlinePaymentNotify';
            [$driver, ] = explode('.', $patron['cat_username'], 2);

            $user = $this->getUser();
            if (!$user) {
                return;
            }

            $patronProfile = array_merge(
                $patron,
                $catalog->getMyProfile($patron)
            );

            // Start payment
            $result = $paymentHandler->startPayment(
                $returnUrl,
                $notifyUrl,
                $user,
                $patronProfile,
                $driver,
                $payableOnline['amount'],
                $view->transactionFee,
                $payableFines,
                $paymentConfig['currency'],
                $transactionIdParam
            );
            $this->flashMessenger()->addMessage(
                $result ? $result : 'online_payment_failed',
                'error'
            );
            header("Location: " . $this->getServerUrl('myresearch-fines'));
            exit();
        }

        $request = $this->getRequest();
        $transactionId = $request->getQuery()->get($transactionIdParam);
        if ($transactionId
            && ($transaction = $trTable->getTransaction($transactionId))
        ) {
            if ($transaction->isRegistered()) {
                // Already registered, treat as success:
                $this->flashMessenger()
                    ->addSuccessMessage('online_payment_successful');
            } else {
                // Process payment response:
                $result = $paymentHandler->processPaymentResponse(
                    $transaction,
                    $this->getRequest()
                );
                if ($result['success']) {
                    if ($result['message']) {
                        $this->flashMessenger()
                            ->addSuccessMessage($result['message']);
                    }
                    if ($result['markFeesAsPaid']) {
                        // Display page and mark fees as paid via AJAX:
                        $view->registerPayment = true;
                        $view->registerPaymentParams = [
                            'transactionId' => $transaction->transaction_id
                        ];
                    }
                } else {
                    if ($result['message']) {
                        $this->flashMessenger()->addErrorMessage($result['message']);
                    }
                }
            }
        }

        if (!$view->registerPayment) {
            // Check if there is a payment in progress
            // or if the user has unregistered payments
            $transactionMaxDuration = $paymentConfig['transactionMaxDuration'] ?? 30;
            $paymentPermittedForUser = $trTable->isPaymentPermitted(
                $patron['cat_username'],
                $transactionMaxDuration
            );
            $allowPayment = $paymentPermittedForUser === true && $payableOnline
                && $payableOnline['payable'] && $payableOnline['amount'];

            // Store current fines to session:
            $this->storeFines($patron, $payableOnline['amount']);
            $session = $this->getOnlinePaymentSession();
            $view->transactionId = $session->sessionId;

            if (!empty($session->paymentOk)) {
                $this->flashMessenger()->addMessage(
                    'online_payment_successful',
                    'success'
                );
                unset($session->paymentOk);
            }

            $view->onlinePaymentEnabled = $allowPayment;
            if ($paymentPermittedForUser !== true) {
                $this->flashMessenger()->addErrorMessage(
                    strip_tags($paymentPermittedForUser)
                );
            } elseif (!empty($payableOnline['reason'])) {
                $view->nonPayableReason = $payableOnline['reason'];
            } elseif ($this->formWasSubmitted('pay')) {
                $view->setTemplate(
                    'Helpers/OnlinePayment/terms-' . $view->paymentHandler
                    . '.phtml'
                );
            }
        }
    }

    /**
     * Store fines to session.
     *
     * @param object $patron Patron.
     * @param int    $amount Total amount to pay without fees
     *
     * @return void
     */
    protected function storeFines($patron, $amount)
    {
        $session = $this->getOnlinePaymentSession();
        $session->sessionId = $this->generateFingerprint($patron);
        $session->amount = $amount;
    }

    /**
     * Log error message.
     *
     * @param string $msg Error message.
     *
     * @return void
     */
    protected function handleError($msg)
    {
        $this->setLogger($this->serviceLocator->get(\VuFind\Log\Logger::class));
        $this->logError($msg);
    }

    /**
     * Log exception.
     *
     * @param Exception $e Exception
     *
     * @return void
     */
    protected function handleException($e)
    {
        $this->setLogger($this->serviceLocator->get(\VuFind\Log\Logger::class));
        if (PHP_SAPI !== 'cli') {
            if ($this->logger instanceof \VuFind\Log\Logger) {
                $this->logger->logException($e, new Parameters());
            }
        } else {
            $this->logException($e);
        }
    }
}
