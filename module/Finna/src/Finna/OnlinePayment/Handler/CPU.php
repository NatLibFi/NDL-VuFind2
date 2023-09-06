<?php

/**
 * CPU payment handler
 *
 * PHP version 8
 *
 * Copyright (C) The National Library of Finland 2016-2022.
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
 * @author   Leszek Manicki <leszek.z.manicki@helsinki.fi>
 * @author   Samuli Sillanpää <samuli.sillanpaa@helsinki.fi>
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @author   Juha Luoma <juha.luoma@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/vufind2:developer_manual Wiki
 */

namespace Finna\OnlinePayment\Handler;

use Finna\OnlinePayment\Handler\Connector\Cpu\Client;
use Finna\OnlinePayment\Handler\Connector\Cpu\Payment;
use Finna\OnlinePayment\Handler\Connector\Cpu\Product;

use function count;
use function in_array;
use function intval;
use function strlen;

/**
 * CPU payment handler module.
 *
 * @category VuFind
 * @package  OnlinePayment
 * @author   Leszek Manicki <leszek.z.manicki@helsinki.fi>
 * @author   Samuli Sillanpää <samuli.sillanpaa@helsinki.fi>
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @author   Juha Luoma <juha.luoma@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/vufind2:developer_manual Wiki
 */
class CPU extends AbstractBase
{
    public const CPU_STATUS_SUCCESS = 1;
    public const CPU_STATUS_CANCELLED = 0;
    public const CPU_STATUS_PENDING = 2;
    public const CPU_STATUS_ID_EXISTS = 97;
    public const CPU_STATUS_ERROR = 98;
    public const CPU_STATUS_INVALID_REQUEST = 99;

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
        $patronId = $patron['cat_username'];
        $transactionId = $this->generateTransactionId($patronId);

        $returnUrl = $this->addQueryParams(
            $returnBaseUrl,
            [$paymentParam => $transactionId]
        );
        $notifyUrl = $this->addQueryParams(
            $notifyBaseUrl,
            [$paymentParam => $transactionId]
        );

        $payment = new Payment($transactionId);
        $email = trim($user->email);
        if ($email) {
            $payment->Email = $email;
        }
        $lastname = $user->lastname;
        if (!empty($user->firstname)) {
            $payment->FirstName = $user->firstname;
        } else {
            // We don't have both names separately, try to extract first name from
            // last name.
            if (strpos($lastname, ',') > 0) {
                // Lastname, Firstname
                [$lastname, $firstname] = explode(',', $lastname, 2);
            } else {
                // First Middle Last
                if (preg_match('/^(.*) (.*?)$/', $lastname, $matches)) {
                    $firstname = $matches[1];
                    $lastname = $matches[2];
                } else {
                    $firstname = '';
                }
            }
            $lastname = trim($lastname);
            $firstname = trim($firstname);
            $payment->FirstName = empty($firstname) ? 'ei tietoa' : $firstname;
        }
        $payment->LastName = empty($lastname) ? 'ei tietoa' : $lastname;

        $lang = $this->getCurrentLanguageCode();
        if (!empty($this->config->supportedLanguages)) {
            $languageMappings = [];
            foreach (explode(':', $this->config->supportedLanguages) as $item) {
                $parts = explode('=', $item, 2);
                if (count($parts) != 2) {
                    continue;
                }
                $languageMappings[trim($parts[0])] = trim($parts[1]);
            }
            if (isset($languageMappings[$lang])) {
                $payment->Language = $languageMappings[$lang];
            }
        }

        $payment->Description = $this->config->paymentDescription ?? '';

        $payment->ReturnAddress = $returnUrl;
        $payment->NotificationAddress = $notifyUrl;

        if (!isset($this->config->productCode)) {
            $this->logPaymentError(
                'missing productCode configuration option',
                compact('user', 'patron', 'fines')
            );
            return '';
        }
        $productCode = $this->config->productCode;
        $productCodeMappings = $this->getProductCodeMappings();
        $organizationProductCodeMappings
            = $this->getOrganizationProductCodeMappings();

        foreach ($fines as $fine) {
            $fineType = $fine['fine'] ?? '';
            $fineOrg = $fine['organization'] ?? '';
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
            if (!empty($fine['title'])) {
                $fineDesc .= ' ('
                    . mb_substr(
                        $fine['title'],
                        0,
                        100 - 4 - strlen($fineDesc),
                        'UTF-8'
                    ) . ')';
            }
            if ($fineDesc) {
                // Get rid of characters that cannot be converted to ISO-8859-1 since
                // CPU apparently can't handle them properly.
                $fineDesc = iconv(
                    'ISO-8859-1',
                    'UTF-8',
                    iconv('UTF-8', 'ISO-8859-1//IGNORE', $fineDesc)
                );
                // Remove ' since it causes the string to be truncated
                $fineDesc = str_replace("'", ' ', $fineDesc);
                // Sanitize before limiting the length, otherwise the sanitization
                // process may blow the string through the limit
                $fineDesc = Client::sanitize($fineDesc);
                // Make sure that description length does not exceed CPU max limit of
                // 100 characters.
                $fineDesc = mb_substr($fineDesc, 0, 100, 'UTF-8');
            }

            $code = $productCodeMappings[$fineType] ?? $productCode;
            if (isset($organizationProductCodeMappings[$fineOrg])) {
                $code = $organizationProductCodeMappings[$fineOrg]
                    . ($productCodeMappings[$fineType] ?? '');
            }
            $code = mb_substr($code, 0, 25, 'UTF-8');
            $product = new Product(
                $code,
                1,
                round($fine['balance']),
                $fineDesc ?: null
            );
            $payment = $payment->addProduct($product);
        }
        if ($transactionFee) {
            $code = $this->config->transactionFeeProductCode ?? $productCode;
            $product = new Product(
                $code,
                1,
                $transactionFee,
                'Palvelumaksu / Serviceavgift / Transaction fee'
            );
            $payment = $payment->addProduct($product);
        }

        if (!($module = $this->initCpu())) {
            $this->logPaymentError(
                'error initializing CPU online payment',
                compact('user', 'patron', 'fines')
            );
            return '';
        }

        try {
            $response = $module->sendPayment($payment);
        } catch (\Exception $e) {
            $this->logPaymentError(
                'exception sending payment: ' . $e->getMessage(),
                compact('user', 'patron', 'fines', 'payment')
            );
            return '';
        }
        if (isset($response['error']) || !$response) {
            $errorMessage = $response['error'] ?? 'sendPayment returned false';
            $this->logPaymentError(
                'error sending payment: ' . $errorMessage,
                compact('user', 'patron', 'fines', 'payment')
            );
            return '';
        }

        $response = json_decode($response);

        if (empty($response->Id) || empty($response->Status)) {
            $this->logPaymentError(
                'error starting payment, no response',
                compact('user', 'patron', 'fines', 'payment')
            );
            return '';
        }

        $status = intval($response->Status);
        $error = in_array(
            $status,
            [self::CPU_STATUS_ERROR, self::CPU_STATUS_INVALID_REQUEST]
        );
        if ($error) {
            // System error or Request failed.
            $this->logPaymentError(
                'error starting transaction',
                compact('response', 'user', 'patron', 'fines', 'payment')
            );
            return '';
        }

        $params = [
            $transactionId, $status,
            $response->Reference, $response->PaymentAddress,
        ];
        if (!$this->verifyHash($params, $response->Hash)) {
            $this->logPaymentError(
                'error starting transaction, invalid checksum',
                compact('response', 'user', 'patron', 'fines', 'payment')
            );
            return '';
        }

        if ($status === self::CPU_STATUS_SUCCESS) {
            // Already processed
            $this->logPaymentError(
                'error starting transaction, transaction already processed',
                compact('response', 'user', 'patron', 'fines', 'payment')
            );
            return '';
        }

        if ($status === self::CPU_STATUS_ID_EXISTS) {
            // Order exists
            $this->logPaymentError(
                'error starting transaction, order exists',
                compact('response', 'user', 'patron', 'fines', 'payment')
            );
            return '';
        }

        if ($status === self::CPU_STATUS_CANCELLED) {
            // Cancelled
            $this->logPaymentError(
                'error starting transaction, order cancelled',
                compact('response', 'user', 'patron', 'fines', 'payment')
            );
            return '';
        }

        if ($status === self::CPU_STATUS_PENDING) {
            // Pending

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
                return '';
            }
            $this->redirectToPayment($response->PaymentAddress);
        }
        return '';
    }

    /**
     * Process the response from payment service.
     *
     * @param \Finna\Db\Row\Transaction $transaction Transaction
     * @param \Laminas\Http\Request     $request     Request
     *
     * @return array One of the result codes defined in AbstractBase and bool
     * indicating whether the transaction was just now marked as paid
     */
    public function processPaymentResponse(
        \Finna\Db\Row\Transaction $transaction,
        \Laminas\Http\Request $request
    ): array {
        if (!($params = $this->getPaymentResponseParams($request))) {
            return [self::PAYMENT_FAILURE, false];
        }

        // Make sure the transaction IDs match:
        if ($transaction->transaction_id !== $params['Id']) {
            return [self::PAYMENT_FAILURE, false];
        }

        $status = intval($params['Status']);
        if ($status === self::CPU_STATUS_SUCCESS) {
            $marked = $transaction->setPaid();
            return [self::PAYMENT_SUCCESS, $marked];
        } elseif ($status === self::CPU_STATUS_CANCELLED) {
            $transaction->setCanceled();
            return [self::PAYMENT_CANCEL, false];
        }

        $this->logPaymentError("unknown status $status");
        return [self::PAYMENT_FAILURE, false];
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
        $params = array_merge(
            $request->getQuery()->toArray(),
            $request->getPost()->toArray()
        );
        $payload = json_decode($request->getContent());

        $required = ['Id', 'Status', 'Reference', 'Hash'];
        $response = [];

        foreach ($required as $name) {
            if (isset($payload->$name)) {
                $response[$name] = $payload->$name;
                continue;
            }
            if (isset($params[$name])) {
                $response[$name] = $params[$name];
                continue;
            }

            $this->logPaymentError(
                "missing parameter $name in payment response",
                compact('request', 'params', 'payload')
            );

            return false;
        }

        $hashParams = [
            $response['Id'],
            intval($response['Status']),
            $response['Reference'],
        ];
        if (!$this->verifyHash($hashParams, $response['Hash'])) {
            $this->logPaymentError(
                'error processing response: invalid checksum',
                compact('request', 'params')
            );
            return false;
        }

        return array_merge($response, $params);
    }

    /**
     * Init CPU module with configured merchantId, secret and URL.
     *
     * @return Client
     */
    protected function initCpu()
    {
        foreach (['merchantId', 'secret', 'url'] as $req) {
            if (!isset($this->config[$req])) {
                $this->logger->err("missing parameter $req");
                return false;
            }
        }

        $module = new Client(
            $this->config['url'],
            $this->config['merchantId'],
            $this->config['secret']
        );
        $module->setHttpService($this->http);
        $module->setLogger($this->logger);
        return $module;
    }

    /**
     * Verify transaction response hash.
     *
     * @param array  $params Parameters
     * @param string $hash   Hash
     *
     * @return boolean
     */
    protected function verifyHash($params, $hash)
    {
        $params[] = $this->config['secret'];
        return hash('sha256', implode('&', $params)) === $hash;
    }
}
