<?php
/**
 * Online payment controller trait.
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
 * @package  Controller
 * @author   Leszek Manicki <leszek.z.manicki@helsinki.fi>
 * @author   Samuli Sillanpää <samuli.sillanpaa@helsinki.fi>
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:controllers Wiki
 */
namespace Finna\Controller;

use Finna\Db\Row\Transaction;
use Laminas\Stdlib\Parameters;
use TCPDF;

/**
 * Online payment controller trait.
 *
 * @category VuFind
 * @package  Controller
 * @author   Leszek Manicki <leszek.z.manicki@helsinki.fi>
 * @author   Samuli Sillanpää <samuli.sillanpaa@helsinki.fi>
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
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
     * Support method for handling online payments.
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
            $this->handleDebugMsg(
                "No online payment handler defined for {$patron['source']}"
            );
            return;
        }

        $session = $this->getOnlinePaymentSession();
        $catalog = $this->getILS();

        // Check if online payment configuration exists for the ILS driver
        $paymentConfig = $catalog->getConfig('onlinePayment', $patron);
        if (empty($paymentConfig)) {
            $this->handleDebugMsg(
                "No online payment ILS configuration for {$patron['source']}"
            );
            return;
        }

        // Check if payment handler is configured in datasources.ini
        $onlinePayment = $this->serviceLocator
            ->get(\Finna\OnlinePayment\OnlinePayment::class);
        if (!$onlinePayment->isEnabled($patron['source'])) {
            $this->handleDebugMsg(
                "Online payment not enabled for {$patron['source']}"
            );
            return;
        }

        // Check if online payment is enabled for the ILS driver
        if (!$catalog->checkFunction('markFeesAsPaid', compact('patron'))) {
            $this->handleDebugMsg(
                "markFeesAsPaid not available for {$patron['source']}"
            );
            return;
        }

        // Check that mandatory settings exist
        if (!isset($paymentConfig['currency'])) {
            $this->handleError(
                "Mandatory setting 'currency' missing from ILS driver for"
                . " '{$patron['source']}'"
            );
            return;
        }

        if (!($user = $this->getUser())) {
            $this->handleError('Could not get user');
            return;
        }

        $selectFees = $paymentConfig['selectFines'] ?? false;
        $pay = $this->formWasSubmitted('pay-confirm');
        $selectedIds = ($selectFees && $pay)
            ? $this->getRequest()->getPost()->get('selectedIDS', [])
            : null;
        $payableOnline = $catalog->getOnlinePaymentDetails(
            $patron,
            $fines,
            $selectedIds
        );
        if ($selectedIds && empty($payableOnline['fines'])) {
            $this->handleError(
                "Fines to pay missing from ILS driver for '{$patron['source']}'"
            );
            return false;
        }

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
        $view->selectFees = $selectFees;

        $trTable = $this->getTable('transaction');
        $lastTransaction = $trTable->getLastPaidForPatron($patron['cat_username']);
        if ($lastTransaction
            && $this->params()->fromQuery('transactionReport') === 'true'
        ) {
            $data = $this->createTransactionReportPDF($lastTransaction);
            header('Content-Type: application/pdf');
            header(
                'Content-disposition: inline; filename="' .
                addcslashes($data['filename'], '"') . '"'
            );
            echo $data['pdf'];
            exit(0);
        }
        $view->lastTransaction = $lastTransaction;

        $paymentInProgress = $trTable->isPaymentInProgress($patron['cat_username']);
        $transactionIdParam = 'finna_payment_id';
        if ($pay && $session && $payableOnline
            && $payableOnline['payable'] && $payableOnline['amount']
            && !$paymentInProgress
        ) {
            // Check CSRF:
            $csrfValidator = $this->serviceLocator
                ->get(\VuFind\Validator\CsrfInterface::class);
            $csrf = $this->getRequest()->getPost()->get('csrf');
            if (!$csrfValidator->isValid($csrf)) {
                $this->flashMessenger()->addErrorMessage('online_payment_failed');
                header("Location: " . $this->getServerUrl('myresearch-fines'));
                exit();
            }
            // After successful token verification, clear list to shrink session and
            // ensure that the form is not re-sent:
            $csrfValidator->trimTokenList(0);

            // Payment requested, do preliminary checks:
            if ($trTable->isPaymentInProgress($patron['cat_username'])) {
                $this->flashMessenger()->addErrorMessage('online_payment_failed');
                header("Location: " . $this->getServerUrl('myresearch-fines'));
                exit();
            }
            if ((($paymentConfig['exactBalanceRequired'] ?? true)
                || !empty($paymentConfig['creditUnsupported']))
                && !$selectFees
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
                $payableOnline['fines'] ?? $payableFines,
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
            $this->ensureLogger();
            $this->logger->warn(
                'Online payment response handler called. Request: '
                . (string)$request
            );

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
                $this->logger->warn(
                    "Online payment response for $transactionId result: $result"
                );
                if ($paymentHandler::PAYMENT_SUCCESS === $result) {
                    $this->flashMessenger()
                        ->addSuccessMessage('online_payment_successful');
                    // Display page and mark fees as paid via AJAX:
                    $view->registerPayment = true;
                    $view->registerPaymentParams = [
                        'transactionId' => $transaction->transaction_id
                    ];
                } elseif ($paymentHandler::PAYMENT_CANCEL === $result) {
                    $this->flashMessenger()
                        ->addSuccessMessage('online_payment_canceled');
                } elseif ($paymentHandler::PAYMENT_FAILURE === $result) {
                    $this->flashMessenger()
                        ->addErrorMessage('online_payment_failed');
                }
            }
        }

        if (!$view->registerPayment) {
            if ($paymentInProgress) {
                $this->flashMessenger()
                    ->addErrorMessage('online_payment_registration_failed');
            } else {
                // Check if payment is permitted:
                $allowPayment = $payableOnline
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
                $view->selectedIds
                    = $this->getRequest()->getPost()->get('selectedIDS', []);
                if (!empty($payableOnline['reason'])) {
                    $view->nonPayableReason = $payableOnline['reason'];
                } elseif ($this->formWasSubmitted('pay')) {
                    $view->setTemplate(
                        'Helpers/OnlinePayment/terms-' . $view->paymentHandler
                        . '.phtml'
                    );
                }
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
     * Make sure that logger is available.
     *
     * @return void
     */
    protected function ensureLogger(): void
    {
        if (null === $this->getLogger()) {
            $this->setLogger($this->serviceLocator->get(\VuFind\Log\Logger::class));
        }
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
        $this->ensureLogger();
        $this->logError($msg);
    }

    /**
     * Log a debug message.
     *
     * @param string $msg Debug message.
     *
     * @return void
     */
    protected function handleDebugMsg($msg)
    {
        $this->ensureLogger();
        $this->logger->debug($msg);
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
        $this->ensureLogger();
        if (PHP_SAPI !== 'cli') {
            if ($this->logger instanceof \VuFind\Log\Logger) {
                $this->logger->logException($e, new Parameters());
            }
        } elseif (is_callable([$this, 'logException'])) {
            $this->logException($e);
        }
    }

    /**
     * Create a transaction breakdown PDF
     *
     * @param Transaction $transaction Transaction
     *
     * @return array
     */
    protected function createTransactionReportPDF(
        Transaction $transaction
    ): array {
        [$source] = explode('.', $transaction->cat_username);
        $sourceName = $this->translate('source_' . $source);

        $dateConverter = $this->serviceLocator->get(\VuFind\Date\Converter::class);
        $paidDate = $dateConverter->convertToDisplayDateAndTime(
            'Y-m-d H:i:s',
            $transaction->paid
        );

        $left = 10;
        $right = 200;
        $bottom = 280;

        $view = $this->getViewRenderer();
        $safeMoneyFormat = $view->plugin('safeMoneyFormat');
        $translationEmpty = $view->plugin('translationEmpty');

        $dataSourceConfig = $this->getConfig('datasources')->toArray();
        $config = $dataSourceConfig[$source] ?? [];
        if ($orgId = $config['onlinePayment']['organisationInfoId'] ?? '') {
            $urlHelper = $view->plugin('url');
            $contactInfo = $urlHelper(
                'organisationinfo-home',
                [],
                [
                    'query' => [
                        'id' => $orgId,
                    ],
                    'force_canonical' => true,
                ]
            );
        } else {
            $contactInfo = $config['onlinePayment']['contactInfo'] ?? '';
        }

        $heading = $this->translate('Payment::breakdown_title') . " - $sourceName";
        $pdf = new TCPDF();
        $pdf->SetCreator('Finna');
        $pdf->SetTitle($heading . ' - ' . $paidDate);
        $pdf->SetMargins($left, 18);
        $pdf->SetHeaderMargin(10);
        $pdf->SetHeaderData('', 0, $heading);
        $pdf->SetFooterMargin(10);
        $pdf->SetAutoPageBreak(false);
        $pdf->AddPage();

        $addInfo = function ($heading, $value) use ($pdf) {
            $pdf->SetFont('helvetica', 'B', 10);
            $pdf->Cell(60, 0, $this->translate($heading));
            $pdf->SetFont('helvetica', '', 10);
            $pdf->Cell(120, 0, $value);
            $pdf->Ln();
        };

        // Print information array:
        $pdf->setY(25);
        $addInfo('Payment::Recipient', $sourceName);
        $addInfo('Payment::Date', $paidDate);
        $addInfo('Payment::Identifier', $transaction->transaction_id);
        if ($contactInfo) {
            $addInfo('Payment::Contact Information', $contactInfo);
        }

        // Print lines:
        $printHeaders = function (TCPDF $pdf) use ($left, $right) {
            $pdf->SetFont('helvetica', 'B', 10);
            $pdf->Cell(40, 0, $this->translate('Payment::Identifier'), 0, 0);
            $pdf->Cell(40, 0, $this->translate('Payment::Type'), 0, 0);
            $pdf->Cell(70, 0, $this->translate('Payment::Details'), 0, 0);
            $pdf->Cell(40, 0, $this->translate('Payment::Fee'), 0, 1, 'R');
            $pdf->SetFont('helvetica', '', 10);
            $y = $pdf->GetY() + 1;
            $pdf->Line($left, $y, $right, $y);
            $pdf->SetY($y + 1);
        };

        $printLine = function (TCPDF $pdf, $fine)
            use ($translationEmpty, $safeMoneyFormat, $left)
        {
            $type = $fine->type;
            if (!$translationEmpty("fine_status_$type")) {
                $type = "fine_status_$type";
            } elseif (!$translationEmpty("status_$type")) {
                // Fallback to item status translations for backwards-compatibility
                $type = "status_$type";
            }

            $curY = $pdf->GetY();
            $pdf->Cell(40, 0, $fine->fine_id ?? '');
            $pdf->MultiCell(40, 0, $this->translate($type), 0, 'L');
            $nextY = $pdf->GetY();
            $pdf->SetXY($left + 80, $curY);
            $pdf->MultiCell(70, 0, $fine->title, 0, 'L');
            $nextY = max($nextY, $pdf->GetY());
            $pdf->SetXY($left + 150, $curY);
            $pdf->Cell(
                40,
                0,
                $safeMoneyFormat($fine->amount / 100.00, $fine->currency),
                0,
                0,
                'R'
            );
            $pdf->setY($nextY);
        };

        $pdf->SetY($pdf->GetY() + 10);
        $printHeaders($pdf);
        // Account for the "Total" line:
        $linesBottom = $bottom - 7;
        foreach ($transaction->getFines() as $fine) {
            $savePDF = clone $pdf;

            $printLine($pdf, $fine);
            // If we exceed bottom, revert and add a new page:
            if ($pdf->GetY() > $linesBottom) {
                $pdf = $savePDF;
                $pdf->AddPage();
                $pdf->SetY(25);
                $printHeaders($pdf);
                $printLine($pdf, $fine);
            }
        }
        $pdf->SetY($pdf->GetY() + 1);
        $pdf->SetFont('helvetica', 'B', 10);
        $amount = $safeMoneyFormat(
            $transaction->amount / 100.00,
            $transaction->currency
        );
        $pdf->Cell(
            190,
            0,
            $this->translate('Payment::Total') . " $amount" ,
            0,
            1,
            'R'
        );

        // Print VAT summary:
        $printVATSummary = function (TCPDF $pdf)
            use ($left, $amount, $safeMoneyFormat, $right)
        {
            $pdf->SetY($pdf->GetY() + 15);
            $pdf->SetFont('helvetica', 'B', 10);
            $vatLeft = $left + 50;
            $pdf->SetX($vatLeft);
            $pdf->Cell(30, 0, $this->translate('Payment::VAT Breakdown'), 0, 0, 'L');
            $pdf->Cell(20, 0, $this->translate('Payment::VAT Percent'));
            $pdf->Cell(30, 0, $this->translate('Payment::Excluding VAT'), 0, 0, 'R');
            $pdf->Cell(30, 0, $this->translate('Payment::VAT'), 0, 0, 'R');
            $pdf->Cell(30, 0, $this->translate('Payment::Including VAT'), 0, 1, 'R');
            $pdf->SetFont('helvetica', '', 10);
            $pdf->SetX($vatLeft);
            $y = $pdf->GetY() + 1;
            $pdf->Line($vatLeft, $y, $vatLeft + 30, $y, ['dash' => '1,2']);
            $pdf->Line($vatLeft + 30, $y, $right, $y, ['dash' => 0]);
            $pdf->SetXY($vatLeft + 30, $y + 1);
            $pdf->Cell(20, 0, '0 %');
            $pdf->Cell(30, 0, $amount, 0, 0, 'R');
            $pdf->Cell(30, 0, $safeMoneyFormat(0), 0, 0, 'R');
            $pdf->Cell(30, 0, $amount, 0, 1, 'R');
        };

        $savePDF = clone $pdf;
        $printVATSummary($pdf);
        if ($pdf->GetY() > $bottom) {
            $pdf = $savePDF;
            $pdf->AddPage();
            $printVATSummary($pdf);
        }

        $date = strtotime($transaction->paid);
        return [
            'pdf' => $pdf->getPDFData(),
            'filename' => $heading . ' - ' . date('Y-m-d H-i', $date),
        ];
    }
}
