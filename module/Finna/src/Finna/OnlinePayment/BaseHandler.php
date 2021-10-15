<?php
/**
 * Payment base handler
 *
 * PHP version 7
 *
 * Copyright (C) The National Library of Finland 2016-2018.
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
namespace Finna\OnlinePayment;

use Laminas\I18n\Translator\TranslatorInterface;
use Laminas\Log\LoggerAwareInterface;

/**
 * Payment base handler
 *
 * @category VuFind
 * @package  OnlinePayment
 * @author   Leszek Manicki <leszek.z.manicki@helsinki.fi>
 * @author   Samuli Sillanpää <samuli.sillanpaa@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/vufind2:developer_manual Wiki
 * @link     http://docs.paytrail.com/ Paytrail API documentation
 */
abstract class BaseHandler implements OnlinePaymentHandlerInterface,
    LoggerAwareInterface
{
    use \VuFind\Db\Table\DbTableAwareTrait {
        getDbTable as getTable;
    }
    use \VuFind\Log\LoggerAwareTrait;

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
     * Translator
     *
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * Constructor
     *
     * @param \Laminas\Config\Config  $config     Configuration as key-value pairs.
     * @param \VuFindHttp\HttpService $http       HTTP service
     * @param TranslatorInterface     $translator Translator
     */
    public function __construct(
        \Laminas\Config\Config $config,
        \VuFindHttp\HttpService $http,
        TranslatorInterface $translator
    ) {
        $this->config = $config;
        $this->http = $http;
        $this->translator = $translator;
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
     * Store transaction to database.
     *
     * @param string $orderNumber    ID
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
        $orderNumber,
        $driver,
        $userId,
        $patronId,
        $amount,
        $transactionFee,
        $currency,
        $fines
    ) {
        $t = $this->getTable('transaction')->createTransaction(
            $orderNumber,
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

        $feeTable = $this->getTable('fee');
        foreach ($fines as $fine) {
            // Sanitize fine strings
            $fine['fine'] = iconv('UTF-8', 'UTF-8//IGNORE', $fine['fine'] ?? '');
            $fine['title'] = iconv('UTF-8', 'UTF-8//IGNORE', $fine['title'] ?? '');
            if (!$feeTable->addFee($t->id, $fine, $t->user_id, $t->currency)) {
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
     * Return started transaction from database.
     *
     * @param string $id Transaction ID
     *
     * @return array Array:
     * - true|false success
     * - string|Finna\Db\Table\Row\Transaction
     * Transaction or error message (translation key).
     */
    protected function getStartedTransaction($id)
    {
        $table = $this->getTable('transaction');
        if (!$table->isTransactionInProgress($id)) {
            return [
                false, 'online_payment_transaction_already_processed_or_unknown'
            ];
        }

        if (($t = $table->getTransaction($id)) === false) {
            $this->logError(
                "error retrieving started transaction $id: transaction not found"
            );
            return [false, 'transaction_found'];
        }

        return [true, $t];
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
     * Set transaction paid.
     *
     * @param string $orderNum  Transaction ID.
     * @param string $timestamp Time stamp.
     *
     * @return void
     */
    protected function setTransactionPaid($orderNum, $timestamp = null)
    {
        if (!$timestamp) {
            $timestamp = time();
        }
        $table = $this->getTable('transaction');
        if (!$table->setTransactionPaid($orderNum, $timestamp)) {
            $this->logError("error updating transaction $orderNum to paid");
        }
    }

    /**
     * Set transaction cancelled.
     *
     * @param string $orderNum Transaction ID.
     *
     * @return void
     */
    protected function setTransactionCancelled($orderNum)
    {
        $table = $this->getTable('transaction');
        if (!$table->setTransactionCancelled($orderNum)) {
            $this->logError("error updating transaction $orderNum to cancelled");
        }
    }

    /**
     * Set transaction failed.
     *
     * @param string $orderNum Transaction ID.
     * @param string $msg      Message
     *
     * @return void
     */
    protected function setTransactionFailed($orderNum, $msg = null)
    {
        $table = $this->getTable('transaction');
        if (!$table->setTransactionRegistrationFailed($orderNum, $msg)) {
            $this->logError("error updating transaction $orderNum to failed");
        }
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
                } elseif ($value instanceof \Cpu_Client_Payment
                    || $value instanceof \Finna\OnlinePayment\Paytrail\PaytrailE2
                    || $value instanceof \Finna\OnlinePayment\TurkuPayment
                ) {
                    $value = var_export($value, true);
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
}
