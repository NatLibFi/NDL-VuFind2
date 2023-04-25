<?php

/**
 * Abstract payment handler
 *
 * PHP version 7
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
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/vufind2:developer_manual Wiki
 * @link     http://docs.paytrail.com/ Paytrail API documentation
 */

namespace Finna\OnlinePayment\Handler;

use Finna\Db\Table\Fee;
use Finna\Db\Table\Transaction;
use Laminas\Log\LoggerAwareInterface;
use VuFind\I18n\Locale\LocaleSettings;
use VuFind\I18n\Translator\TranslatorAwareInterface;

/**
 * Abstract payment handler
 *
 * @category VuFind
 * @package  OnlinePayment
 * @author   Leszek Manicki <leszek.z.manicki@helsinki.fi>
 * @author   Samuli Sillanpää <samuli.sillanpaa@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/vufind2:developer_manual Wiki
 * @link     http://docs.paytrail.com/ Paytrail API documentation
 */
abstract class AbstractBase implements
    HandlerInterface,
    LoggerAwareInterface,
    TranslatorAwareInterface
{
    use \VuFind\Log\LoggerAwareTrait;
    use \VuFind\I18n\Translator\TranslatorAwareTrait;

    /**
     * Result codes for processPaymentResponse
     *
     * @var int
     */
    public const PAYMENT_SUCCESS = 0; // Successful payment, mark fees paid
    public const PAYMENT_CANCEL = 1;  // Payment canceled
    public const PAYMENT_FAILURE = 2; // Payment failed
    public const PAYMENT_PENDING = 3; // Payment in progress

    /**
     * Configuration.
     *
     * @var \Laminas\Config\Config
     */
    protected $config;

    /**
     * HTTP service.
     *
     * @var \VuFindHttp\HttpService
     */
    protected $http;

    /**
     * Locale settings
     *
     * @var LocaleSettings
     */
    protected $localeSettings;

    /**
     * Transaction table
     *
     * @var Transaction
     */
    protected $transactionTable;

    /**
     * Fee table
     *
     * @var Fee
     */
    protected $feeTable;

    /**
     * Constructor
     *
     * @param \VuFindHttp\HttpService $http             HTTP service
     * @param LocaleSettings          $locale           Locale settings
     * @param Transaction             $transactionTable Transaction table
     * @param Fee                     $feeTable         Fee table
     */
    public function __construct(
        \VuFindHttp\HttpService $http,
        LocaleSettings $locale,
        Transaction $transactionTable,
        Fee $feeTable
    ) {
        $this->http = $http;
        $this->localeSettings = $locale;
        $this->transactionTable = $transactionTable;
        $this->feeTable = $feeTable;
    }

    /**
     * Initialize the handler
     *
     * @param \Laminas\Config\Config $config Online payment configuration
     *
     * @return void
     */
    public function init(\Laminas\Config\Config $config): void
    {
        $this->config = $config;
    }

    /**
     * Return name of handler.
     *
     * @return string name
     */
    public function getName()
    {
        return $this->config->onlinePayment->handler;
    }

    /**
     * Generate the internal payment transaction identifer.
     *
     * @param string $patronId Patron's Catalog Username (barcode)
     *
     * @return string Transaction identifier
     */
    protected function generateTransactionId($patronId)
    {
        return md5($patronId . '_' . microtime(true));
    }

    /**
     * Add query parameters to an url
     *
     * @param string $url    URL
     * @param array  $params Parameters to add
     *
     * @return string
     */
    protected function addQueryParams(string $url, array $params): string
    {
        $url .= strpos($url, '?') === false ? '?' : '&';
        $url .= http_build_query($params);
        return $url;
    }

    /**
     * Store transaction to database.
     *
     * @param string $transactionId  Transaction ID
     * @param string $driver         Patron MultiBackend ILS source
     * @param int    $userId         User ID
     * @param string $patronId       Patron's catalog username
     * (e.g. barcode)
     * @param int    $amount         Amount
     * (excluding transaction fee)
     * @param int    $transactionFee Transaction fee
     * @param string $currency       Currency
     * @param array  $fines          Fines data
     *
     * @return boolean success
     */
    protected function createTransaction(
        $transactionId,
        $driver,
        $userId,
        $patronId,
        $amount,
        $transactionFee,
        $currency,
        $fines
    ) {
        $t = $this->transactionTable->createTransaction(
            $transactionId,
            $driver,
            $userId,
            $patronId,
            $amount,
            $transactionFee,
            $currency
        );

        if (!$t) {
            $this->logError(
                'error creating transaction',
                compact('userId', 'patronId', 'fines')
            );
            return false;
        }

        foreach ($fines as $fine) {
            // Sanitize fine strings
            $fine['fine'] = iconv('UTF-8', 'UTF-8//IGNORE', $fine['fine'] ?? '');
            $fine['title'] = iconv('UTF-8', 'UTF-8//IGNORE', $fine['title'] ?? '');
            if (!$this->feeTable->addFee($t->id, $fine, $t->user_id, $t->currency)) {
                $this->logError(
                    'error adding fee to transaction',
                    compact('userId', 'patronId', 'fines', 'fine')
                );
                return false;
            }
        }

        return true;
    }

    /**
     * Return transaction from database.
     *
     * @param string $id Transaction ID
     *
     * @return ?\Finna\Db\Row\Transaction
     */
    protected function getTransaction($id)
    {
        if (!($t = $this->transactionTable->getTransaction($id))) {
            $this->logError(
                "error retrieving transaction $id: transaction not found"
            );
            return null;
        }

        return $t;
    }

    /**
     * Redirect to payment handler.
     *
     * @param string $url URL
     *
     * @return void
     */
    protected function redirectToPayment($url)
    {
        header("Location: $url", true, 302);
        exit();
    }

    /**
     * Get product code mappings from configuration
     *
     * @return array
     */
    protected function getProductCodeMappings()
    {
        $mappings = [];
        if (!empty($this->config->productCodeMappings)) {
            foreach (explode(':', $this->config->productCodeMappings) as $item) {
                $parts = explode('=', $item, 2);
                if (count($parts) != 2) {
                    continue;
                }
                $mappings[trim($parts[0])] = trim($parts[1]);
            }
        }
        return $mappings;
    }

    /**
     * Get organization to product code mappings from configuration
     *
     * @return array
     */
    protected function getOrganizationProductCodeMappings()
    {
        $mappings = [];
        if (!empty($this->config->organizationProductCodeMappings)) {
            $map = explode(':', $this->config->organizationProductCodeMappings);
            foreach ($map as $item) {
                $parts = explode('=', $item, 2);
                if (count($parts) != 2) {
                    continue;
                }
                $mappings[trim($parts[0])] = trim($parts[1]);
            }
        }
        return $mappings;
    }

    /**
     * Log an error
     *
     * @param string $msg  Error message
     * @param array  $data Additional data to log
     *
     * @return void
     */
    protected function logPaymentError($msg, $data = [])
    {
        $msg = "Online payment: $msg";
        if ($data) {
            $msg .= ". Additional data:\n" . $this->dumpData($data);
        }
        $this->logError($msg);
    }

    /**
     * Extract first name and last name from user
     *
     * @param \Finna\Db\Row\User $user User
     *
     * @return array Associative array with 'firstname' and 'lastname'
     */
    protected function extractUserNames(\Finna\Db\Row\User $user): array
    {
        $lastname = trim($user->lastname);
        if (!empty($user->firstname)) {
            $firstname = trim($user->firstname);
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
        }
        return compact('firstname', 'lastname');
    }

    /**
     * Dump a data array with mixed content
     *
     * @param array  $data   Data array
     * @param string $indent Indentation string
     *
     * @return string
     */
    protected function dumpData($data, $indent = '')
    {
        if (strlen($indent) > 6) {
            return '';
        }

        $results = [];

        foreach ($data as $key => $value) {
            if (is_object($value)) {
                if (method_exists($value, 'toArray')) {
                    $value = $value->toArray();
                } else {
                    $key = "$key: " . get_class($value);
                    $value = get_object_vars($value);
                }
            }
            if (is_array($value)) {
                $results[] = "$key: {\n"
                    . $this->dumpData($value, $indent . '  ')
                    . "\n$indent}";
            } else {
                $results[] = "$key: " . var_export($value, true);
            }
        }

        return $indent . implode(",\n$indent", $results);
    }

    /**
     * Get two character language code from user's current locale
     *
     * @return string
     */
    protected function getCurrentLanguageCode()
    {
        [$lang] = explode('-', $this->localeSettings->getUserLocale(), 2);
        return $lang;
    }
}
