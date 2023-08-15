<?php

/**
 * Paytrail Payment API HTTP request client
 *
 * PHP version 8
 *
 * Copyright (C) The National Library of Finland 2023.
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

use Paytrail\SDK\Exception\ClientException;
use Paytrail\SDK\Exception\RequestException;
use Paytrail\SDK\Response\CurlResponse;

/**
 * Paytrail Payment API HTTP request client
 *
 * @category VuFind
 * @package  OnlinePayment
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/vufind2:developer_manual Wiki
 * @link     http://docs.paytrail.com/ Paytrail API documentation
 */
class RequestClient extends \Paytrail\SDK\Util\RequestClient
{
    use \Finna\OnlinePayment\OnlinePaymentHttpRequestTrait;

    /**
     * Request base URL
     *
     * @var string
     */
    protected $requestBaseUrl;

    /**
     * Constructor
     *
     * @param string $baseUrl Request base URL
     */
    public function __construct(string $baseUrl)
    {
        $this->requestBaseUrl = $baseUrl;
    }

    /**
     * Perform HTTP request.
     *
     * @param string $method      Method
     * @param string $uri         URI
     * @param array  $options     Options
     * @param bool   $formRequest Whether this is a form request
     *
     * @return mixed
     * @throws ClientException
     * @throws RequestException
     */
    public function request(string $method, string $uri, array $options, bool $formRequest = false)
    {
        $result = $this->sendHttpRequest(
            $method,
            $this->buildUrl($uri, $options),
            $this->formatBody($options['body'] ?? '', $formRequest),
            [
                'connect_timeout' => Client::DEFAULT_TIMEOUT,
                'timeout' => Client::DEFAULT_TIMEOUT,
            ],
            $options['headers'] ?? []
        );
        if (false === $result) {
            throw new RequestException('Request failed');
        }
        return new CurlResponse(
            $this->convertHeadersToString($result['headers']),
            $result['response'],
            $result['httpCode']
        );
    }

    /**
     * Build URL by prefixing endpoint with base URL and appending possible query parameters.
     *
     * @param string $uri     URI
     * @param array  $options Options
     *
     * @return string
     */
    protected function buildUrl(string $uri, array $options): string
    {
        if (!empty($options['query'])) {
            $uri .= '?' . http_build_query($options['query']);
        }
        return $this->requestBaseUrl . $uri;
    }

    /**
     * Format request body
     *
     * @param string|array $body        Body
     * @param bool         $formRequest Whether this is a form request
     *
     * @return string
     */
    protected function formatBody(string|array $body, bool $formRequest): string
    {
        // Decode form request:
        if ($formRequest) {
            $body = json_decode($body, true);
        }
        return is_array($body) ? http_build_query($body, '', '&') : $body;
    }

    /**
     * Convert array of headers to a string
     *
     * @param array $headers Headers
     *
     * @return string
     */
    protected function convertHeadersToString(array $headers): string
    {
        return implode(
            "\n",
            array_map(
                function ($key, $value) {
                    return "$key: $value";
                },
                array_keys($headers),
                $headers
            )
        );
    }
}
