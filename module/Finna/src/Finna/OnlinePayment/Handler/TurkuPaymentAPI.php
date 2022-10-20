<?php
/**
 * Turku Payment API handler
 *
 * PHP version 7
 *
 * Copyright (C) The National Library of Finland 2022.
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
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/vufind2:developer_manual Wiki
 */
namespace Finna\OnlinePayment\Handler;

use Finna\OnlinePayment\Handler\Connector\Paytrail\PaytrailPaymentAPI\Customer;
use Finna\OnlinePayment\Handler\Connector\TurkuPaymentAPI\Client;
use Finna\OnlinePayment\Handler\Connector\TurkuPaymentAPI\Item;
use Finna\OnlinePayment\Handler\Connector\TurkuPaymentAPI\PaymentRequest;
use Finna\OnlinePayment\Handler\Connector\TurkuPaymentAPI\TurkuSignature;
use Paytrail\SDK\Model\CallbackUrl;

/**
 * Turku Payment API handler
 *
 * @category VuFind
 * @package  OnlinePayment
 * @author   Juha Luoma <juha.luoma@helsinki.fi>
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/vufind2:developer_manual Wiki
 */
class TurkuPaymentAPI extends AbstractBase
{
    /**
     * Mappings from VuFind language codes to Paytrail
     *
     * @var array
     */
    protected $languageMap = [
        'fi' => 'FI',
        'sv' => 'SV',
        'en' => 'EN'
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

        $returnUrls = (new CallbackUrl())
            ->setSuccess($returnUrl)
            ->setCancel($returnUrl);

        $callbackUrls = (new CallbackUrl())
            ->setSuccess($notifyUrl)
            ->setCancel($notifyUrl);

        $customer = (new Customer())
            ->setEmail(trim($user->email));

        $language = $this->languageMap[$this->getCurrentLanguageCode()] ?? 'FI';
        $sapOrganization = [
            'sapSalesOrganization' => $this->config->sapSalesOrganization ?? '',
            'sapDistributionChannel' => $this->config->sapDistributionChannel ?? '',
            'sapSector' => $this->config->sapSector ?? '',
        ];
        $reference = preg_replace('/\PL/u', '', "{$transactionId}{$patronId}");
        $paymentRequest = (new PaymentRequest())
            ->setUsePricesWithoutVat(true)
            ->setSapOrganizationDetails($sapOrganization)
            ->setStamp($transactionId)
            ->setRedirectUrls($returnUrls)
            ->setCallbackUrls($callbackUrls)
            ->setReference($reference)
            ->setCurrency('EUR')
            ->setLanguage($language)
            ->setAmount($amount + $transactionFee)
            ->setCustomer($customer);

        // Payment description in $this->config->paymentDescription is not supported

        if (isset($this->config->productCode)
            || isset($this->config->transactionFeeProductCode)
            || isset($this->config->productCodeMappings)
            || isset($this->config->organizationProductCodeMappings)
        ) {
            // Map fines to items:
            $productCode = !empty($this->config->productCode)
                ? $this->config->productCode : '';
            $productCodeMappings = $this->getProductCodeMappings();
            $organizationProductCodeMappings
                = $this->getOrganizationProductCodeMappings();
            $items = [];
            $sapProduct = [
                'sapCode' => $this->config->sapCode ?? '',
                'sapOfficeCode' => $this->config->sapOfficeCode ?? ''
            ];
            foreach ($fines as $fine) {
                $fineType = $fine['fine'] ?? '';
                $fineOrg = $fine['organization'] ?? '';

                if (isset($productCodeMappings[$fineType])) {
                    $code = $productCodeMappings[$fineType];
                } elseif ($productCode) {
                    $code = $productCode;
                } else {
                    $code = $fineType;
                }
                if (isset($organizationProductCodeMappings[$fineOrg])) {
                    $code = $organizationProductCodeMappings[$fineOrg]
                        . ($productCodeMappings[$fineType] ?? '');
                }
                $code = mb_substr($code, 0, 100, 'UTF-8');

                $fineDesc = '';
                if (!empty($fineType)) {
                    $fineDesc
                        = $this->translator->translate("fine_status_$fineType");
                    if ("fine_status_$fineType" === $fineDesc) {
                        $fineDesc = $this->translator->translate("status_$fineType");
                        if ("status_$fineType" === $fineDesc) {
                            $fineDesc = $fineType;
                        }
                    }
                }
                if (!empty($fine['title'])) {
                    $title = mb_substr(
                        $fine['title'],
                        0,
                        1000 - 4 - mb_strlen($fineDesc, 'UTF-8'),
                        'UTF-8'
                    );
                    $fineDesc .= " ($title)";
                }
                $item = (new Item())
                    ->setSapProduct($sapProduct)
                    ->setDescription($fineDesc)
                    ->setProductCode($code)
                    ->setUnitPrice($fine['balance'])
                    ->setUnits(1)
                    ->setVatPercentage(0);

                $items[] = $item;
            }
            if ($transactionFee) {
                $code = $this->config->transactionFeeProductCode ?? $productCode;
                $item = (new Item())
                    ->setSapProduct($sapProduct)
                    ->setDescription(
                        'Palvelumaksu / Serviceavgift / Transaction fee'
                    )
                    ->setProductCode($code)
                    ->setUnitPrice($transactionFee)
                    ->setUnits(1)
                    ->setVatPercentage(0);

                $items[] = $item;
            }
            $paymentRequest->setItems($items);
        }

        try {
            $paymentResponse = $this->initClient()->createPayment($paymentRequest);
        } catch (\Exception $e) {
            $request = json_encode($paymentRequest, JSON_PRETTY_PRINT);
            $this->logPaymentError(
                'exception sending payment: ' . $e->getMessage(),
                compact('user', 'patron', 'fines', 'request')
            );
            return '';
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

        $this->redirectToPayment($paymentResponse->getHref());

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
        if ($transaction->transaction_id !== $params['checkout-stamp']) {
            return self::PAYMENT_FAILURE;
        }

        $status = $params['checkout-status'];
        switch ($status) {
        case 'ok':
            $transaction->setPaid();
            return self::PAYMENT_SUCCESS;
        case 'fail':
            $transaction->setCanceled();
            return self::PAYMENT_CANCEL;
        case 'new':
        case 'pending':
        case 'delayed':
            return self::PAYMENT_PENDING;
        }

        $this->logPaymentError("unknown status $status");
        return self::PAYMENT_FAILURE;
    }

    /**
     * Validate and return payment response parameters.
     *
     * @param \Laminas\Http\Request $request Request
     *
     * @return array|false
     */
    public function getPaymentResponseParams($request)
    {
        $params = [];
        $required = [];
        $body = '';
        // Payment response is a get request and notify is a post request
        if ($request->isGet()) {
            $params = $request->getQuery()->toArray();
            $required = [
                'checkout-amount',
                'checkout-reference',
                'checkout-stamp',
                'checkout-status',
                'checkout-provider',
                'checkout-transaction-id',
                'X-TURKU-SP',
                'X-TURKU-TS',
                'Authorization'
            ];
        } elseif ($request->isPost()) {
            $params = $request->getHeaders()->toArray();
            $body = $request->getContent();
            $required = [
                'X-Turku-Sp',
                'X-Turku-Ts',
                'Authorization'
            ];
        } else {
            $this->logPaymentError(
                'The request was not POST or GET'
            );
            return false;
        }
        foreach ($required as $name) {
            if (empty($params[$name])) {
                $this->logPaymentError(
                    "missing or empty parameter $name in payment response",
                    compact('params')
                );
                return false;
            }
        }
        // Validate the parameters:
        try {
            TurkuSignature::validateHash(
                $params,
                $body,
                $params['Authorization'],
                $this->config['secret'] ?? '',
                $params['X-TURKU-TS'] ?? $params['X-Turku-Ts'] ?? '',
                $this->config['platformName']
            );
        } catch (\Exception $e) {
            $this->logPaymentError(
                'parameter Authorization validation failed',
                compact('params')
            );
            return false;
        }
        // For POST notify request, the params needed to check are in the body
        return $request->isGet() ? $params : json_decode($body, true);
    }

    /**
     * Initialize the Paytrail client
     *
     * @return Client
     */
    protected function initClient(): Client
    {
        foreach (['merchantId', 'secret', 'oId', 'url', 'platformName'] as $req) {
            if (!isset($this->config[$req])) {
                $this->logPaymentError("missing parameter $req");
                throw new \Exception('Missing parameter');
            }
        }

        $client = new Client(
            $this->config->merchantId,
            $this->config->oId,
            $this->config->secret,
            $this->config->platformName,
            $this->config->url
        );
        $client->setHttpService($this->http);
        $client->setLogger($this->logger);
        return $client;
    }
}
