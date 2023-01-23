<?php
/**
 * Turku payment handler
 *
 * PHP version 7
 *
 * Copyright (C) The National Library of Finland 2014-2022.
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
 * @author   Juha Luoma <juha.luoma@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/vufind2:developer_manual Wiki
 */
namespace Finna\OnlinePayment\Handler;

use Finna\OnlinePayment\Handler\Connector\TurkuPayment\TurkuPaytrailE2;

/**
 * Turku online payment handler module.
 *
 * @category VuFind
 * @package  OnlinePayment
 * @author   Juha Luoma <juha.luoma@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/vufind2:developer_manual Wiki
 *
 * @deprecated Use TurkuPaymentAPI
 */
class TurkuPayment extends AbstractBase
{
    /**
     * Mappings from VuFind language codes to Paytrail
     *
     * @var array
     */
    protected $languageMap = [
        'fi' => 'fi_FI',
        'sv' => 'sv_SE',
        'en' => 'en_US'
    ];

    /**
     * Start transaction.
     *
     * @param string             $returnBaseUrl  Return URL
     * @param string             $notifyBaseUrl  Notify URL
     * @param \Finna\Db\Row\User $user           User
     * @param array              $patron         Patron information
     * @param string             $driver         Patron MultiBackend ILS source
     * @param int                $amount         Amount (excluding transaction fee)
     * @param int                $transactionFee Transaction fee
     * @param array              $fines          Fines data
     * @param string             $currency       Currency
     * @param string             $paymentParam   Payment status URL parameter
     *
     * @return string Error message on error, otherwise redirects to payment handler.
     */
    public function startPayment(
        $returnBaseUrl,
        $notifyBaseUrl,
        $user,
        $patron,
        $driver,
        $amount,
        $transactionFee,
        $fines,
        $currency,
        $paymentParam
    ) {
        $required = ['merchantId', 'secret', 'sapCode', 'oId', 'applicationName'];
        foreach ($required as $req) {
            if (!isset($this->config[$req])) {
                $this->logPaymentError("missing parameter $req");
                throw new \Exception('Missing parameter');
            }
        }

        $patronId = $patron['cat_username'];
        $transactionId = $this->generateTransactionId($patronId);

        $module = $this->initTurkuPaytrail();

        $returnUrl = $this->addQueryParams(
            $returnBaseUrl,
            [$paymentParam => $transactionId]
        );
        $notifyUrl = $this->addQueryParams(
            $notifyBaseUrl,
            [$paymentParam => $transactionId]
        );

        $module->setUrls($returnUrl, $returnUrl, $notifyUrl);
        $module->setOrderNumber($transactionId);
        $module->setCurrency($currency);
        $module->setOid($this->config->oId);
        $module->setApplicationName($this->config->applicationName);
        $module->setSapCode($this->config->sapCode);

        if (!empty($this->config->paymentDescription)) {
            $module->setMerchantDescription(
                $this->config->paymentDescription . " - $patronId"
            );
        } else {
            $module->setMerchantDescription($patronId);
        }

        foreach ($fines as $fine) {
            $fineType = $fine['fine'] ?? '';
            $code = mb_substr($fineType, 0, 16, 'UTF-8');

            $fineDesc = '';
            if (!empty($fineType)) {
                $fineDesc = $this->translator->translate("fine_status_$fineType");
                if ("fine_status_$fineType" === $fineDesc) {
                    $fineDesc = $this->translator->translate("status_$fineType");
                    if ("status_$fineType" === $fineDesc) {
                        $fineDesc = $fineType;
                    }
                }
            }

            $module->addProduct(
                $fineDesc,
                $code,
                1,
                round($fine['balance']),
                0,
                TurkuPaytrailE2::TYPE_NORMAL
            );
        }
        if ($transactionFee) {
            $code = $this->config->transactionFeeProductCode ??
                $this->config->productCode ?? '';
            $module->addProduct(
                'Palvelumaksu / Serviceavgift / Transaction fee',
                $code,
                1,
                $transactionFee,
                0,
                TurkuPaytrailE2::TYPE_HANDLING
            );
        }

        try {
            $module->generateBody();
        } catch (\Exception $e) {
            $this->logPaymentError(
                'error creating payment request body: ' . $e->getMessage(),
                compact('user', 'patron', 'fines', 'module')
            );
            return false;
        }

        $success = $this->createTransaction(
            $transactionId,
            $driver,
            $user->id,
            $patronId,
            $amount,
            $transactionFee,
            $currency,
            $fines
        );
        if (!$success) {
            return false;
        }
        // This will redirect if successful:
        $result = $module->sendRequest($this->config->url);
        $this->logPaymentError(
            'error sending payment request: ' . $result,
            compact('user', 'patron', 'fines', 'module')
        );
        $this->getTransaction($transactionId)->setCanceled();
        return '';
    }

    /**
     * Process the response from payment service.
     *
     * @param \Finna\Db\Row\Transaction $transaction Transaction
     * @param \Laminas\Http\Request     $request     Request
     *
     * @return int One of the result codes defined in AbstractBase
     */
    public function processPaymentResponse(
        \Finna\Db\Row\Transaction $transaction,
        \Laminas\Http\Request $request
    ): int {
        if (!($params = $this->getPaymentResponseParams($request))) {
            return self::PAYMENT_FAILURE;
        }

        // Make sure the transaction IDs match:
        if ($transaction->transaction_id !== $params['ORDER_NUMBER']) {
            return self::PAYMENT_FAILURE;
        }

        if (!empty($params['PAID'])) {
            $transaction->setPaid($params['TIMESTAMP']);
            return self::PAYMENT_SUCCESS;
        }

        $transaction->setCanceled();
        return self::PAYMENT_CANCEL;
    }

    /**
     * Validate and return payment response parameters.
     *
     * @param Laminas\Http\Request $request Request
     *
     * @return array
     */
    protected function getPaymentResponseParams($request)
    {
        if (!($module = $this->initTurkuPaytrail())) {
            return false;
        }

        $params = array_merge(
            $request->getQuery()->toArray(),
            $request->getPost()->toArray()
        );

        $required = [
            'ORDER_NUMBER', 'TIMESTAMP', 'RETURN_AUTHCODE'
        ];

        foreach ($required as $name) {
            if (!isset($params[$name])) {
                $this->logPaymentError(
                    "missing parameter $name in payment response",
                    compact('params')
                );
                return false;
            }
        }

        if (!empty($params['PAID'])) {
            // Validate a 'success' request:
            $success = $module->validateSuccessRequest(
                $params['ORDER_NUMBER'],
                $params['TIMESTAMP'],
                $params['PAID'],
                $params['METHOD'] ?? '',
                $params['RETURN_AUTHCODE']
            );
            if (!$success) {
                $this->logPaymentError(
                    'error processing success response: invalid checksum',
                    compact('request', 'params')
                );
                return false;
            }
        } else {
            // Validate a 'cancel' request:
            $success = $module->validateCancelRequest(
                $params['ORDER_NUMBER'],
                $params['TIMESTAMP'],
                $params['RETURN_AUTHCODE']
            );
            if (!$success) {
                $this->logPaymentError(
                    'error processing success response: invalid checksum',
                    compact('request', 'params')
                );
                return false;
            }
        }

        return $params;
    }

    /**
     * Initialize the Turku Paytrail module
     *
     * @return TurkuPaytrailE2
     */
    protected function initTurkuPaytrail()
    {
        $module = new TurkuPaytrailE2(
            $this->config->merchantId,
            $this->config->secret,
            $this->languageMap[$this->getCurrentLanguageCode()] ?? 'en_US'
        );
        $module->setHttpService($this->http);
        $module->setLogger($this->logger);

        return $module;
    }
}
