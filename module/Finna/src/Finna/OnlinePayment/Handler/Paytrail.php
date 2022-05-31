<?php
/**
 * Paytrail E2 (legacy) payment handler
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
 * @author   Leszek Manicki <leszek.z.manicki@helsinki.fi>
 * @author   Samuli Sillanpää <samuli.sillanpaa@helsinki.fi>
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/vufind2:developer_manual Wiki
 * @link     http://docs.paytrail.com/ Paytrail API documentation
 */
namespace Finna\OnlinePayment\Handler;

use Finna\OnlinePayment\Handler\Connector\Paytrail\PaytrailE2;

/**
 * Paytrail E2 (legacy) payment handler module.
 *
 * @category VuFind
 * @package  OnlinePayment
 * @author   Leszek Manicki <leszek.z.manicki@helsinki.fi>
 * @author   Samuli Sillanpää <samuli.sillanpaa@helsinki.fi>
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/vufind2:developer_manual Wiki
 * @link     http://docs.paytrail.com/ Paytrail API documentation
 */
class Paytrail extends AbstractBase
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
        $patronId = $patron['cat_username'];
        $transactionId = $this->generateTransactionId($patronId);

        $module = $this->initPaytrail($transactionId, $currency);

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

        if (!empty($this->config->paymentDescription)) {
            $module->setMerchantDescription(
                $this->config->paymentDescription . " - $patronId"
            );
        } else {
            $module->setMerchantDescription($patronId);
        }

        $lastname = trim($user->lastname);
        if (!empty($user->firstname)) {
            $module->setFirstName(trim($user->firstname));
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
            $module->setFirstName(empty($firstname) ? 'ei tietoa' : $firstname);
        }
        $module->setLastName(empty($lastname) ? 'ei tietoa' : $lastname);

        $email = trim($user->email);
        if ($email) {
            $module->setEmail($email);
        }

        if (!isset($this->config->productCode)
            && !isset($this->config->transactionFeeProductCode)
            && !isset($this->config->productCodeMappings)
            && !isset($this->config->organizationProductCodeMappings)
        ) {
            $module->setTotalAmount($amount + $transactionFee);
        } else {
            $productCode = !empty($this->config->productCode)
                ? $this->config->productCode : '';
            $productCodeMappings = $this->getProductCodeMappings();
            $organizationProductCodeMappings
                = $this->getOrganizationProductCodeMappings();

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
                $code = mb_substr($code, 0, 16, 'UTF-8');

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
                    $fineDesc .= ' ('
                        . mb_substr(
                            $fine['title'],
                            0,
                            255 - 4 - strlen($fineDesc),
                            'UTF-8'
                        ) . ')';
                }
                $module->addProduct(
                    $fineDesc,
                    $code,
                    1,
                    $fine['balance'],
                    0,
                    PaytrailE2::TYPE_NORMAL
                );
            }
            if ($transactionFee) {
                $code = $this->config->transactionFeeProductCode ?? $productCode;
                $module->addProduct(
                    'Palvelumaksu / Serviceavgift / Transaction fee',
                    $code,
                    1,
                    $transactionFee,
                    0,
                    PaytrailE2::TYPE_HANDLING
                );
            }
        }

        try {
            $formData = $module->createPaymentFormData();
        } catch (\Exception $e) {
            $this->logPaymentError(
                'error creating payment form data: ' . $e->getMessage(),
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
            return '';
        }

        $paytrailUrl = !empty($this->config->e2url) ? $this->config->e2url
            : 'https://payment.paytrail.com/e2';

        $this->redirectToPaymentForm($paytrailUrl, $formData);
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

        $status = $params['STATUS'];
        $timestamp = $params['TIMESTAMP'];

        if ('PAID' === $status) {
            $transaction->setPaid($timestamp);
            return self::PAYMENT_SUCCESS;
        } elseif ('CANCELLED' === $status) {
            $transaction->setCanceled();
            return self::PAYMENT_CANCEL;
        }

        $this->logPaymentError("unknown status $status");
        return self::PAYMENT_FAILURE;
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
        if (!($module = $this->initPaytrail())) {
            return false;
        }

        $params = array_merge(
            $request->getQuery()->toArray(),
            $request->getPost()->toArray()
        );

        $required = [
            'ORDER_NUMBER', 'PAYMENT_ID', 'TIMESTAMP', 'STATUS', 'RETURN_AUTHCODE'
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

        $success = $module->validateRequest(
            $params['ORDER_NUMBER'],
            $params['PAYMENT_ID'],
            $params['TIMESTAMP'],
            $params['STATUS'],
            $params['RETURN_AUTHCODE']
        );
        if (!$success) {
            $this->logPaymentError(
                'error processing response: invalid checksum',
                compact('request', 'params')
            );
            return [
                'success' => false,
                'markFeesAsPaid' => false,
                'error' => 'online_payment_failed'
            ];
        }

        return $params;
    }

    /**
     * Initialize the Paytrail module
     *
     * @return PaytrailE2
     */
    protected function initPaytrail()
    {
        foreach (['merchantId', 'secret'] as $req) {
            if (!isset($this->config[$req])) {
                $this->logPaymentError("missing parameter $req");
                throw new \Exception('Missing parameter');
            }
        }

        return new PaytrailE2(
            $this->config->merchantId,
            $this->config->secret,
            $this->languageMap[$this->getCurrentLanguageCode()] ?? 'en_US'
        );
    }

    /**
     * Redirect to payment handler.
     *
     * @param string $url      URL
     * @param array  $formData Form fields
     *
     * @return void
     */
    protected function redirectToPaymentForm($url, $formData)
    {
        // Output a minimal form and submit it automatically
        $formFields = '';
        foreach ($formData as $key => $value) {
            $formFields .= '<input type="hidden" name="' . htmlentities($key)
                . '" value="' . htmlentities($value) . '">';
        }
        $lang = $this->getCurrentLanguageCode();
        $title = $this->translator->translate('online_payment_go_to_pay');
        $title = str_replace('%%amount%%', '', $title);
        $jsRequired = $this->translator->translate('Please enable JavaScript.');
        echo <<<EOT
<!DOCTYPE html>
<html lang="$lang">
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge"/>
    <meta name="viewport" content="width=device-width,initial-scale=1.0"/>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <title>$title</title>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('paytrail-form').submit();
        });
    </script>
</head>
<body>
    <noscript>
        $jsRequired
    </noscript>
    <form id="paytrail-form" action="$url" method="POST">
        $formFields
    </form>
</body>
</html>
EOT;
        exit();
    }
}
