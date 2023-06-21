<?php

/**
 * Paytrail Payment API client
 *
 * PHP version 8
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

use Laminas\Log\LoggerInterface;
use VuFindHttp\HttpService;

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
    use \VuFind\Log\LoggerAwareTrait;

    /**
     * HTTP Service
     *
     * @var HttpService
     */
    protected $httpService;

    /**
     * Logger
     *
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * Service base URL
     *
     * @var string
     */
    protected $baseUrl;

    /**
     * Client constructor.
     *
     * @param int             $merchantId   The merchant.
     * @param string          $secretKey    The secret key.
     * @param string          $platformName Platform name.
     * @param HttpService     $http         HTTP service.
     * @param LoggerInterface $logger       Logger.
     * @param string          $baseUrl      Service base url.
     */
    public function __construct(
        int $merchantId,
        string $secretKey,
        string $platformName,
        HttpService $http,
        LoggerInterface $logger,
        string $baseUrl
    ) {
        // Set these first as parent's constructor will call createHttpClient:
        $this->httpService = $http;
        $this->logger = $logger;
        $this->baseUrl = $baseUrl;

        parent::__construct($merchantId, $secretKey, $platformName);
    }

    /**
     * Create HTTP client
     *
     * @return void
     */
    protected function createHttpClient()
    {
        $this->http_client = new RequestClient($this->baseUrl);
        $this->http_client->setHttpService($this->httpService);
        $this->http_client->setLogger($this->logger);
    }
}
