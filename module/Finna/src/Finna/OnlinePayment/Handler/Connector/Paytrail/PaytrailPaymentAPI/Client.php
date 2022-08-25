<?php
/**
 * Paytrail Payment API client
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
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/vufind2:developer_manual Wiki
 * @link     http://docs.paytrail.com/ Paytrail API documentation
 */
namespace Finna\OnlinePayment\Handler\Connector\Paytrail\PaytrailPaymentAPI;

use Paytrail\SDK\Exception\HmacException;
use Paytrail\SDK\Exception\ValidationException;
use Paytrail\SDK\Request\PaymentRequest;
use Paytrail\SDK\Response\PaymentResponse;

/**
 * Paytrail Payment API client
 *
 * @category VuFind
 * @package  OnlinePayment
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/vufind2:developer_manual Wiki
 * @link     http://docs.paytrail.com/ Paytrail API documentation
 */
class Client extends \Paytrail\SDK\Client
{
    use \Finna\OnlinePayment\OnlinePaymentPostRequestTrait;

    /**
     * Client constructor.
     *
     * @param int    $merchantId   The merchant.
     * @param string $secretKey    The secret key.
     * @param string $platformName Platform name.
     */
    public function __construct(
        int $merchantId,
        string $secretKey,
        string $platformName
    ) {
        // N.B. Do not call parent constructor to avoid creating a Guzzle client
        $this->setMerchantId($merchantId);
        $this->setSecretKey($secretKey);
        $this->setPlatformName($platformName);
    }

    /**
     * Create a payment request.
     *
     * @param PaymentRequest $payment A payment class instance.
     *
     * @return PaymentResponse
     * @throws HmacException       Thrown if HMAC calculation fails for responses.
     * @throws ValidationException Thrown if payment validation fails.
     * @throws \Exception          Thrown if the HTTP request fails.
     */
    public function createPayment(PaymentRequest $payment)
    {
        $this->validateRequestItem($payment);

        // Create request and hmac:
        $body = json_encode($payment, JSON_UNESCAPED_SLASHES);
        $headers = $this->getHeaders('POST', null, null);
        $mac = $this->calculateHmac($headers, $body);
        $headers['signature'] = $mac;

        $response = $this->postRequest(
            self::API_ENDPOINT . '/payments',
            $body,
            [],
            $headers
        );
        if (!$response) {
            throw new \Exception('Request failed');
        }

        $body = $response['response'];

        // Handle header data and validate HMAC:
        $responseHeaders = $response['headers'];
        $this->validateHmac(
            $responseHeaders,
            $body,
            $responseHeaders['signature'] ?? ''
        );

        // Create response:
        $decoded = json_decode($body);
        $paymentResponse = (new PaymentResponse())
            ->setTransactionId($decoded->transactionId ?? null)
            ->setHref($decoded->href ?? null)
            ->setProviders($decoded->providers ?? null);

        return $paymentResponse;
    }
}
