<?php
/**
 * Finna ILS online payment Trait
 *
 * PHP version 5
 *
 * Copyright (C) The National Library of Finland 2015-2017.
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
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 * @category VuFind
 * @package  ILS_Drivers
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @author   Samuli Sillanpää <samuli.sillanpaa@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:ils_drivers Wiki
 */
namespace Finna\ILS\Driver;

use VuFind\Exception\ILS as ILSException;

/**
 * Finna ILS online payment Trait
 *
 * @category VuFind
 * @package  ILS_Drivers
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @author   Samuli Sillanpää <samuli.sillanpaa@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:ils_drivers Wiki
 */
trait OnlinePaymentTrait
{
    /**
     * Returns a list of parameters that are required for registering
     * online payments to the ILS. The parameters are configured in
     * OnlinePayment > registrationParams.
     *
     * @return array
     */
    abstract public function getOnlinePaymentRegistrationParams();

    /**
     * Support method for getMyFines that augments the fines with
     * extra information. The driver may also append the information
     * in getMyFines implement markOnlinePayableFines as a stub.
     *
     * The following keys are appended to each fine:
     * - payableOnline <boolean> May the fine be payed online?
     *
     * The following keys are appended when required:
     * - blockPayment <boolean> True if the fine prevents starting
     * the payment process.
     *
     * @param array $fines Processed fines.
     *
     * @return array $fines Fines.
     */
    abstract public function markOnlinePayableFines($fines);

    /**
     * Registers an online payment to the ILS.
     *
     * @param array  $patron   Patron
     * @param int    $amount   Total amount paid
     * @param string $currency Currency
     * @param array  $params   Registration configuration parameters
     *
     * @return boolean success
     */
    abstract public function registerOnlinePayment(
        $patron, $amount, $currency, $params
    );

    /**
     * Get Patron Fines
     *
     * This is responsible for retrieving all fines by a specific patron.
     *
     * @param array $patron The patron array from patronLogin
     *
     * @throws DateException
     * @throws ILSException
     * @return mixed        Array of the patron's fines on success.
     */
    public function getMyFines($patron)
    {
        try {
            $fines = parent::getMyFines($patron);
            return $this->markOnlinePayableFines($fines);
        } catch (ILSException $e) {
            return false;
        }
    }

    /**
     * Return total amount of fees that may be paid online.
     *
     * @param array $patron Patron
     *
     * @throws ILSException
     * @return array Associative array of payment info,
     * false if an ILSException occurred.
     */
    public function getOnlinePayableAmount($patron)
    {
        $fines = $this->getMyFines($patron);
        if (!empty($fines)) {
            $nonPayableReason = false;
            $amount = 0;
            $allowPayment = true;
            foreach ($fines as $fine) {
                if (!$fine['payableOnline']) {
                    $nonPayableReason
                        = 'online_payment_fines_contain_nonpayable_fees';
                } else {
                    $amount += $fine['balance'];
                }
                if ($allowPayment && !empty($fine['blockPayment'])) {
                    $allowPayment = false;
                }
            }
            $config = $this->getConfig('onlinePayment');
            if (!$nonPayableReason
                && isset($config['minimumFee']) && $amount < $config['minimumFee']
            ) {
                $nonPayableReason = 'online_payment_minimum_fee';
            }
            $res = ['payable' => $allowPayment, 'amount' => $amount];
            if ($nonPayableReason) {
                $res['reason'] = $nonPayableReason;
            }

            return $res;
        }
        return [
            'payable' => false,
            'amount' => 0,
            'reason' => 'online_payment_minimum_fee'
        ];
    }

    /**
     * Mark fees as paid.
     *
     * This is called after a successful online payment.
     *
     * @param array  $patron        Patron.
     * @param int    $amount        Amount to be registered as paid.
     * @param string $transactionId Transaction ID.
     *
     * @throws ILSException
     * @return boolean success
     */
    public function markFeesAsPaid($patron, $amount, $transactionId)
    {
        if (!$this->validateOnlinePaymentConfig(true)) {
            throw new ILSException(
                'Online payment disabled or configuration missing.'
            );
        }

        $paymentConfig = $this->getOnlinePaymentConfig();
        $params
            = isset($paymentConfig['registrationParams'])
            ? $paymentConfig['registrationParams'] : []
        ;
        $currency = $paymentConfig['currency'];
        $userId = $patron['id'];
        $patronId = $patron['cat_username'];
        $errFun = function ($userId, $patronId, $error) {
            $this->error(
                "Online payment error (user: $userId, driver: "
                . $this->dbName . ", patron: $patronId): "
                . $error
            );
            throw new ILSException($error);
        };

        $result = $this->registerOnlinePayment(
            $patron, $amount, $currency, $params
        );
        if ($result === true) {
            $cacheId = "blocks_$patronId";
            $this->session->cache[$cacheId] = null;
            return true;
        } elseif ($result !== false) {
            $errFun($userId, $patronId, $result);
        }
        return false;
    }

    /**
     * Get online payment configuration
     *
     * @param boolean $throwException Throw an ILSException if the
     * configuration is not valid.
     *
     * @return array config data
     */
    protected function getOnlinePaymentConfig($throwException = false)
    {
        if (empty($this->config['OnlinePayment'])) {
            return false;
        }
        return $this->config['OnlinePayment'];
    }

    /**
     * Check if online payment is supported and enabled
     *
     * @return bool
     */
    protected function supportsOnlinePayment()
    {
        $config = $this->getOnlinePaymentConfig();
        if (!$config || empty($config['enabled'])) {
            return false;
        }

        return $this->validateOnlinePaymentConfig();
    }

    /**
     * Helper method for validating online payment configuration.
     *
     * @param boolean $throwException Throw an ILSException if the
     * configuration is not valid.
     *
     * @return bool
     */
    protected function validateOnlinePaymentConfig($throwException = false)
    {
        $checkRequired = function ($config, $params, $throwException) {
            foreach ($params as $req) {
                if (!isset($params[$req]) && !empty($params[$req])) {
                    $err = "Missing online payment parameter $req";
                    $this->error($err);
                    if ($throwException) {
                        throw new ILSException($err);
                    }
                    return false;
                }

                if (empty($config[$req])) {
                    return false;
                }
            }
            return true;
        };

        if (!$config = $this->getOnlinePaymentConfig()) {
            return false;
        }
        if (!$checkRequired($config, ['currency', 'enabled'], $throwException)) {
            return false;
        }

        $registrationParams = $this->getOnlinePaymentRegistrationParams();
        if (empty($registrationParams)) {
            return true;
        }

        if (empty($config['registrationParams'])) {
            return false;
        }

        return $checkRequired(
            $config['registrationParams'], $registrationParams, $throwException
        );
    }
}
