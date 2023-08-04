<?php

/**
 * III Sierra REST API driver
 *
 * PHP version 8
 *
 * Copyright (C) The National Library of Finland 2016-2023.
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
 * @package  ILS_Drivers
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:ils_drivers Wiki
 */

namespace Finna\ILS\Driver;

use VuFind\Exception\ILS as ILSException;

/**
 * III Sierra REST API driver
 *
 * @category VuFind
 * @package  ILS_Drivers
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:ils_drivers Wiki
 */
class SierraRest extends \VuFind\ILS\Driver\SierraRest
{
    /**
     * Fine types that allow online payment
     *
     * @var array
     */
    protected $onlinePayableFineTypes = [2, 4, 5, 6];

    /**
     * Manual fine description regexp patterns that allow online payment
     *
     * @var array
     */
    protected $onlinePayableManualFineDescriptionPatterns = [];

    /**
     * Mappings from item status codes to VuFind strings
     *
     * @var array
     */
    protected $itemStatusMappings = [
        '!' => 'On Holdshelf',
        't' => 'In Transit',
        'o' => 'On Reference Desk',
        'k' => 'In Repair',
        'm' => 'Missing',
        'n' => 'Long Overdue',
        '$' => 'lost_loan_and_paid',
        'p' => 'Withdrawn',
        'z' => 'Claims Returned',
        's' => 'On Search',
        'd' => 'In Process',
        '-' => 'On Shelf',
        'Charged' => 'Charged',
        'Ordered' => 'Ordered',
    ];

    /**
     * SOAP options for the IMMS connection
     *
     * @var array
     */
    protected $immsSoapOptions = [
        'soap_version' => SOAP_1_1,
        'exceptions' => true,
        'trace' => false,
        'timeout' => 15,
        'connection_timeout' => 5,
    ];

    /**
     * Days before account expiration to start displaying a notification
     *
     * @var int
     */
    protected $daysBeforeAccountExpirationNotification = 30;

    /**
     * Initialize the driver.
     *
     * Validate configuration and perform all resource-intensive tasks needed to
     * make the driver active.
     *
     * @throws ILSException
     * @return void
     */
    public function init()
    {
        parent::init();

        if ($types = $this->config['OnlinePayment']['fineTypes'] ?? '') {
            $this->onlinePayableFineTypes = explode(',', $types);
        }
        $this->onlinePayableManualFineDescriptionPatterns
            = $this->config['OnlinePayment']['manualFineDescriptions'] ?? [];

        $key = 'daysBeforeAccountExpirationNotification';
        if (isset($this->config['Catalog'][$key])) {
            $this->daysBeforeAccountExpirationNotification
                = $this->config['Catalog'][$key];
        }
    }

    /**
     * Get Patron Holds
     *
     * This is responsible for retrieving all holds by a specific patron.
     *
     * @param array $patron The patron array from patronLogin
     *
     * @throws DateException
     * @throws ILSException
     * @return array        Array of the patron's holds on success.
     * @todo   Support for handling frozen and pickup location change
     */
    public function getMyHolds($patron)
    {
        $holds = parent::getMyHolds($patron);
        foreach ($holds as &$hold) {
            if (!$hold['available']) {
                continue;
            }
            $hold['holdShelf'] = $this->getHoldShelf($hold, $patron);
        }
        unset($hold);

        return $holds;
    }

    /**
     * Get Pick Up Locations
     *
     * This is responsible for getting a list of valid library locations for
     * holds / recall retrieval
     *
     * @param array $patron      Patron information returned by the patronLogin
     * method.
     * @param array $holdDetails Optional array, only passed in when getting a list
     * in the context of placing a hold; contains most of the same values passed to
     * placeHold, minus the patron data. May be used to limit the pickup options
     * or may be ignored. The driver must not add new options to the return array
     * based on this data or other areas of VuFind may behave incorrectly.
     *
     * @throws ILSException
     * @return array        An array of associative arrays with locationID and
     * locationDisplay keys
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function getPickUpLocations($patron = false, $holdDetails = null)
    {
        if (!empty($this->config['pickUpLocations'])) {
            $locations = [];
            foreach ($this->config['pickUpLocations'] as $id => $location) {
                $locations[] = [
                    'locationID' => $id,
                    'locationDisplay' => $this->translateLocation(
                        ['code' => $id, 'name' => $location]
                    ),
                ];
            }
            return $locations;
        }

        $result = $this->makeRequest(
            [$this->apiBase, 'branches', 'pickupLocations'],
            [
                'limit' => 10000,
                'offset' => 0,
                'fields' => 'code,name',
                'language' => $this->getTranslatorLocale(),
            ],
            'GET',
            $patron
        );
        if (!empty($result['code'])) {
            // An error was returned
            $this->error(
                "Request for pickup locations returned error code: {$result['code']}"
                . ", HTTP status: {$result['httpStatus']}, name: {$result['name']}"
            );
            throw new ILSException('Problem with Sierra REST API.');
        }
        if (empty($result)) {
            return [];
        }

        $locations = [];
        foreach ($result as $entry) {
            $locations[] = [
                'locationID' => $entry['code'],
                'locationDisplay' => $entry['name'],
            ];
        }

        usort($locations, [$this, 'pickupLocationSortFunction']);
        return $locations;
    }

    /**
     * Get Patron Profile
     *
     * This is responsible for retrieving the profile for a specific patron.
     *
     * @param array $patron The patron array
     *
     * @throws ILSException
     * @return array        Array of the patron's profile data on success.
     */
    public function getMyProfile($patron)
    {
        $result = $this->makeRequest(
            [$this->apiBase, 'patrons', $patron['id']],
            [
                'fields' => 'names,emails,phones,addresses,birthDate,expirationDate'
                    . ',message,homeLibraryCode,fixedFields',
            ],
            'GET',
            $patron
        );

        if (empty($result)) {
            return [];
        }
        $firstname = '';
        $lastname = '';
        $address = '';
        $zip = '';
        $city = '';
        if (!empty($result['names'])) {
            $nameParts = explode(', ', $result['names'][0], 2);
            $lastname = $nameParts[0];
            $firstname = $nameParts[1] ?? '';
        }
        if (!empty($result['addresses'][0]['lines'][1])) {
            $address = $result['addresses'][0]['lines'][0];
            $postalParts = explode(' ', $result['addresses'][0]['lines'][1], 2);
            if (isset($postalParts[1])) {
                $zip = $postalParts[0];
                $city = $postalParts[1];
            } else {
                $city = $postalParts[0];
            }
        }

        $messages = [];
        foreach ($result['message']['accountMessages'] ?? [] as $message) {
            $messages[] = [
                'message' => $message,
            ];
        }

        $phoneType = $this->config['Profile']['phoneNumberField'] ?? 'p';
        $smsType = $this->config['Profile']['smsNumberField'] ?? 't';
        $phone = '';
        $sms = '';
        foreach ($result['phones'] ?? [] as $entry) {
            if ($phoneType === $entry['type']) {
                $phone = $entry['number'];
            } elseif ($smsType === $entry['type']) {
                $sms = $entry['number'];
            }
        }

        $profile = [
            'firstname' => $firstname,
            'lastname' => $lastname,
            'phone' => $phone,
            'smsnumber' => $sms,
            'email' => !empty($result['emails']) ? $result['emails'][0] : '',
            'address1' => $address,
            'zip' => $zip,
            'city' => $city,
            'birthdate' => $result['birthDate'] ?? '',
            'messages' => $messages,
            'home_library' => $result['homeLibraryCode'],
        ];

        if (!empty($result['expirationDate'])) {
            $profile['expiration_date'] = $this->dateConverter->convertToDisplayDate(
                'Y-m-d',
                $result['expirationDate']
            );
            $date = \DateTime::createFromFormat('Y-m-d', $result['expirationDate']);
            $diff = $date->diff(new \Datetime());
            if (!$diff->invert && $diff->days > 0) {
                $profile['expired'] = true;
            } elseif (
                $this->daysBeforeAccountExpirationNotification
                && $diff->days === 0
                || ($diff->invert
                && $diff->days <= $this->daysBeforeAccountExpirationNotification)
            ) {
                $profile['expiration_soon'] = true;
            }
        }

        // PCODE3: self-service library access
        if ($field = $result['fixedFields'][46] ?? null) {
            $profile['self_service_library'] = (string)$field['value'] === '1';
        }

        // Checkout history:
        $result = $this->makeRequest(
            [
                'v6', 'patrons', $patron['id'], 'checkouts', 'history',
                'activationStatus',
            ],
            [],
            'GET',
            $patron
        );
        if (array_key_exists('readingHistoryActivation', $result)) {
            $profile['loan_history'] = $result['readingHistoryActivation'];
        }

        return $profile;
    }

    /**
     * Update Patron Transaction History State
     *
     * Enable or disable patron's transaction history
     *
     * @param array $patron The patron array from patronLogin
     * @param mixed $state  Any of the configured values
     *
     * @return array Associative array of the results
     */
    public function updateTransactionHistoryState($patron, $state)
    {
        $request = ['readingHistoryActivation' => $state == '1'];
        $result = $this->makeRequest(
            [
                'v6', 'patrons', $patron['id'], 'checkouts', 'history',
                'activationStatus',
            ],
            json_encode($request),
            'POST',
            $patron
        );

        if (!empty($result['code'])) {
            return [
                'success' => false,
                'status' => $this->formatErrorMessage(
                    $result['description'] ?? $result['name']
                ),
            ];
        }
        return ['success' => true, 'status' => 'request_change_done'];
    }

    /**
     * Update patron contact information
     *
     * @param array $patron  Patron array
     * @param array $details Associative array of patron contact information
     *
     * @throws ILSException
     *
     * @return array Associative array of the results
     */
    public function updateAddress($patron, $details)
    {
        // Compose a request from the fields:
        $request = [];
        $addressFields = ['address1' => true, 'zip' => true, 'city' => true];
        if (array_intersect_key($details, $addressFields)) {
            $address1 = $details['address1'] ?? '';
            $zip = $details['zip'] ?? '';
            $city = $details['city'] ?? '';
            $request['addresses'][] = [
                'lines' => array_filter(
                    [
                        $address1,
                        trim("$zip $city"),
                        $details['country'] ?? '',
                    ]
                ),
                'type' => 'a',
            ];
        }
        if (array_key_exists('phone', $details)) {
            $request['phones'][] = [
                'number' => $details['phone'],
                'type' => 'p',
            ];
        }
        if (array_key_exists('smsnumber', $details)) {
            $request['phones'][] = [
                'number' => $details['smsnumber'],
                'type' => 't',
            ];
        }
        if (array_key_exists('email', $details)) {
            $request['emails'][] = $details['email'];
        }
        if ($homeLibrary = $details['home_library']) {
            $request['homeLibraryCode'] = $homeLibrary;
        }

        $result = $this->makeRequest(
            [
                'v6', 'patrons', $patron['id'],
            ],
            json_encode($request),
            'PUT',
            $patron,
            true
        );

        if (!in_array($result['statusCode'], ['200', '204'])) {
            $this->logError(
                'Patron update request failed with status code'
                . " {$result['statusCode']}: "
                . (var_export($result['response'] ?? '', true))
            );
            return [
                'success' => false,
                'status' => 'profile_update_failed',
                'sys_message' => $result['description'] ?? '',
            ];
        }

        return [
            'success' => true,
            'status' => 'request_change_accepted',
            'sys_message' => '',
        ];
    }

    /**
     * Get Patron Fines
     *
     * This is responsible for retrieving all fines by a specific patron.
     *
     * @param array $patron The patron array from patronLogin
     *
     * @throws DateException
     * @throws ILSException
     * @return array        Array of the patron's fines on success.
     */
    public function getMyFines($patron)
    {
        $result = $this->makeRequest(
            [$this->apiBase, 'patrons', $patron['id'], 'fines'],
            [
                'fields' => 'item,assessedDate,description,chargeType,itemCharge'
                    . ',processingFee,billingFee,paidAmount,location,invoiceNumber',
            ],
            'GET',
            $patron
        );

        if (!isset($result['entries'])) {
            return [];
        }
        $fines = [];
        foreach ($result['entries'] as $entry) {
            $amount = $entry['itemCharge'] + $entry['processingFee']
                + $entry['billingFee'];
            $balance = $amount - $entry['paidAmount'];
            $description = '';
            // Display charge type if it's not manual (code=1)
            if (
                !empty($entry['chargeType'])
                && $entry['chargeType']['code'] != '1'
            ) {
                $description = $entry['chargeType']['display'];
            }
            if (!empty($entry['description'])) {
                if ($description) {
                    $description .= ' - ';
                }
                $description .= $entry['description'];
            }
            switch ($description) {
                case 'Overdue Renewal':
                    $description = 'Overdue';
                    break;
            }
            $bibId = null;
            $title = null;
            if (!empty($entry['item'])) {
                $itemId = $this->extractId($entry['item']);
                // Fetch bib ID from item
                $item = $this->makeRequest(
                    [$this->apiBase, 'items', $itemId],
                    ['fields' => 'bibIds'],
                    'GET',
                    $patron
                );
                if (!empty($item['bibIds'])) {
                    $bibId = $item['bibIds'][0];
                    // Fetch bib information
                    $bib = $this->getBibRecord($bibId, null, $patron);
                    $title = $bib['title'] ?? '';
                }
            }

            $fines[] = [
                'amount' => $amount * 100,
                'fine' => $description,
                'balance' => $balance * 100,
                'createdate' => $this->dateConverter->convertToDisplayDate(
                    'Y-m-d',
                    $entry['assessedDate']
                ),
                'checkout' => '',
                'id' => $this->formatBibId($bibId),
                'title' => $title,
                'fine_id' => $this->extractId($entry['id']),
                'organization' => $entry['location']['code'] ?? '',
                'payableOnline' => $balance > 0 && $this->finePayableOnline($entry),
                '__invoiceNumber' => $entry['invoiceNumber'],
            ];
        }
        return $fines;
    }

    /**
     * Return details on fees payable online.
     *
     * @param array  $patron          Patron
     * @param array  $fines           Patron's fines
     * @param ?array $selectedFineIds Selected fines
     *
     * @throws ILSException
     * @return array Associative array of payment details,
     * false if an ILSException occurred.
     */
    public function getOnlinePaymentDetails($patron, $fines, ?array $selectedFineIds)
    {
        if (!$fines) {
            return [
                'payable' => false,
                'amount' => 0,
                'reason' => 'online_payment_minimum_fee',
            ];
        }

        $nonPayableReason = false;
        $amount = 0;
        $payableFines = [];
        foreach ($fines as $fine) {
            if (
                null !== $selectedFineIds
                && !in_array($fine['fine_id'], $selectedFineIds)
            ) {
                continue;
            }
            if ($fine['payableOnline']) {
                $amount += $fine['balance'];
                $payableFines[] = $fine;
            }
        }
        $config = $this->getConfig('onlinePayment');
        $transactionFee = $config['transactionFee'] ?? 0;
        if (
            isset($config['minimumFee'])
            && $amount + $transactionFee < $config['minimumFee']
        ) {
            $nonPayableReason = 'online_payment_minimum_fee';
        }
        $res = [
            'payable' => empty($nonPayableReason),
            'amount' => $amount,
            'fines' => $payableFines,
        ];
        if ($nonPayableReason) {
            $res['reason'] = $nonPayableReason;
        }
        return $res;
    }

    /**
     * Mark fees as paid.
     *
     * This is called after a successful online payment.
     *
     * @param array  $patron            Patron
     * @param int    $amount            Amount to be registered as paid
     * @param string $transactionId     Transaction ID
     * @param int    $transactionNumber Internal transaction number
     * @param ?array $fineIds           Fine IDs to mark paid or null for bulk
     *
     * @throws ILSException
     * @return bool success
     */
    public function markFeesAsPaid(
        $patron,
        $amount,
        $transactionId,
        $transactionNumber,
        $fineIds = null
    ) {
        if (empty($fineIds)) {
            $this->logError('Bulk payment not supported');
            return false;
        }

        $fines = $this->getMyFines($patron);
        if (!$fines) {
            $this->logError('No fines to pay found');
            return false;
        }

        $amountRemaining = $amount;
        $payments = [];
        foreach ($fines as $fine) {
            if (
                in_array($fine['fine_id'], $fineIds)
                && $fine['payableOnline'] && $fine['balance'] > 0
            ) {
                $pay = min($fine['balance'], $amountRemaining);
                $payments[] = [
                    'amount' => $pay,
                    'paymentType' => 1,
                    'invoiceNumber' => (string)$fine['__invoiceNumber'],
                ];
                $amountRemaining -= $pay;
            }
        }
        if (!$payments) {
            $this->logError('Fine IDs do not match any of the payable fines');
            return false;
        }

        $request = [
            'payments' => $payments,
        ];
        $result = $this->makeRequest(
            [
                'v6', 'patrons', $patron['id'], 'fines', 'payment',
            ],
            json_encode($request),
            'PUT',
            $patron,
            true
        );

        if (!in_array($result['statusCode'], ['200', '204'])) {
            $this->logError(
                "Payment request failed with status code {$result['statusCode']}: "
                . (var_export($result['response'] ?? '', true))
            );
            return false;
        }
        // Sierra doesn't support storing any remaining amount, so we'll just have to
        // live with the assumption that any fine amount didn't somehow get smaller
        // during payment. That would be unlikely in any case.
        return true;
    }

    /**
     * Get a password recovery token for a user
     *
     * @param array $params Required params such as cat_username and email
     *
     * @return array Associative array of the results
     */
    public function getPasswordRecoveryToken($params)
    {
        $request = [
            'queries' => [
                [
                    'target' => [
                        'record' => [
                            'type' => 'patron',
                        ],
                        'field' => [
                            'tag' => 'b',
                        ],
                    ],
                    'expr' => [
                        'op' => 'equals',
                        'operands' => [
                            str_replace(' ', '', $params['cat_username']),
                        ],
                    ],
                ],
                'and',
                [
                    'target' => [
                        'record' => [
                            'type' => 'patron',
                        ],
                        'field' => [
                            'tag' => 'z',
                        ],
                    ],
                    'expr' => [
                        'op' => 'equals',
                        'operands' => [
                            trim($params['email']),
                        ],
                    ],
                ],
            ],
        ];

        $result = $this->makeRequest(
            [
                [
                    'type' => 'encoded',
                    'value' => 'v6/patrons/query?offset=0&limit=1',
                ],
            ],
            json_encode($request),
            'POST'
        );

        if (
            $result['total'] === 1
            && $link = $result['entries'][0]['link'] ?? null
        ) {
            $patronId = $this->extractId($link);

            // Check that there's an existing PIN in varFields:
            $result = $this->makeRequest(
                [$this->apiBase, 'patrons', $patronId],
                [
                    'fields' => 'varFields',
                ],
                'GET'
            );
            $pinExists = false;
            foreach ($result['varFields'] ?? [] as $field) {
                if ('=' === $field['fieldTag']) {
                    $pinExists = true;
                    break;
                }
            }
            if (!$pinExists) {
                return [
                    'success' => false,
                    'error' => 'authentication_error_account_locked',
                ];
            }
            return [
                'success' => true,
                'token' => $patronId,
            ];
        }
        return [
            'success' => false,
            'error' => 'recovery_user_not_found',
        ];
    }

    /**
     * Recover user's password with a token from getPasswordRecoveryToken
     *
     * @param array $params Required params such as cat_username, token and new
     * password
     *
     * @return array Associative array of the results
     */
    public function recoverPassword($params)
    {
        $request = [
            'pin' => $params['password'],
        ];
        $result = $this->makeRequest(
            [
                'v6', 'patrons', $params['token'],
            ],
            json_encode($request),
            'PUT',
            false,
            true
        );

        if (!in_array($result['statusCode'], ['200', '204'])) {
            $this->logError(
                'Patron update request failed with status code'
                . " {$result['statusCode']}: "
                . (var_export($result['response'] ?? '', true))
            );
            return [
                'success' => false,
                'error' => 'profile_update_failed',
            ];
        }

        return [
            'success' => true,
        ];
    }

    /**
     * Public Function which retrieves renew, hold and cancel settings from the
     * driver ini file.
     *
     * @param string $function The name of the feature to be checked
     * @param array  $params   Optional feature-specific parameters (array)
     *
     * @return array An array with key-value pairs.
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function getConfig($function, $params = [])
    {
        if ('onlinePayment' === $function) {
            $result = $this->config['OnlinePayment'] ?? [];
            $result['exactBalanceRequired'] = false;
            $result['selectFines'] = true;
            return $result;
        }
        if (
            'getPasswordRecoveryToken' === $function
            || 'recoverPassword' === $function
        ) {
            return !empty($this->config['PasswordRecovery']['enabled'])
                ? $this->config['PasswordRecovery'] : false;
        }
        if ('updateAddress' === $function) {
            $function = 'updateProfile';
            $config = parent::getConfig('updateProfile', $params);
            if (isset($config['fields'])) {
                foreach ($config['fields'] as &$field) {
                    $parts = explode(':', $field);
                    $fieldLabel = $parts[0];
                    $fieldId = $parts[1] ?? '';
                    $fieldRequired = ($parts[3] ?? '') === 'required';
                    if ('home_library' === $fieldId) {
                        $locations = [];
                        $pickUpLocations = $this->getPickUpLocations(
                            $params['patron'] ?? false
                        );
                        foreach ($pickUpLocations as $current) {
                            $locations[$current['locationID']]
                                = $current['locationDisplay'];
                        }
                        $field = [
                            'field' => $fieldId,
                            'label' => $fieldLabel,
                            'type' => 'select',
                            'required' => $fieldRequired,
                            'options' => $locations,
                        ];
                        if ($options = ($parts[4] ?? '')) {
                            $field['options'] = [];
                            foreach (explode(';', $options) as $option) {
                                $keyVal = explode('=', $option, 2);
                                if (isset($keyVal[1])) {
                                    $field['options'][$keyVal[0]] = [
                                        'name' => $keyVal[1],
                                    ];
                                }
                            }
                        }
                    }
                }
                unset($field);
            }
            return $config;
        }

        return parent::getConfig($function, $params);
    }

    /**
     * Return summary of holdings items.
     *
     * @param array $holdings Parsed holdings items
     * @param array $bib      Bibliographic data
     *
     * @return array summary
     */
    protected function getHoldingsSummary($holdings, $bib)
    {
        $availableTotal = 0;
        $locations = [];

        foreach ($holdings as $item) {
            if (!empty($item['availability'])) {
                $availableTotal++;
            }
            $locations[$item['location']] = true;
        }

        // Since summary data is appended to the holdings array as a fake item,
        // we need to add a few dummy-fields that VuFind expects to be
        // defined for all elements.
        $result = [
           'available' => $availableTotal,
           'total' => count($holdings),
           'locations' => count($locations),
           'availability' => null,
           'callnumber' => null,
           'location' => '__HOLDINGSSUMMARYLOCATION__',
        ];
        if ($this->config['Holdings']['display_total_hold_count'] ?? true) {
            $result['reservations'] = $bib['holdCount'] ?? null;
        }
        return $result;
    }

    /**
     * Get Item Statuses
     *
     * This is responsible for retrieving the status information of a certain
     * record.
     *
     * @param string $id            The record id to retrieve the holdings for
     * @param bool   $checkHoldings Whether to check holdings records
     * @param ?array $patron        Patron information, if available
     *
     * @return array An associative array with the following keys:
     * id, availability (boolean), status, location, reserve, callnumber.
     */
    protected function getItemStatusesForBib(string $id, bool $checkHoldings, ?array $patron = null): array
    {
        $bibFields = ['bibLevel'];
        // If we need to look at bib call numbers, retrieve varFields:
        if (!empty($this->config['CallNumber']['bib_fields'])) {
            $bibFields[] = 'varFields';
        }
        // Retrieve orders if needed:
        if (!empty($this->config['Holdings']['display_orders'])) {
            $bibFields[] = 'orders';
        }
        // Retrieve hold count if needed:
        if ($this->config['Holdings']['display_total_hold_count'] ?? true) {
            $bibFields[] = 'holdCount';
        }
        $bib = $this->getBibRecord($id, $bibFields);
        $bibCallNumber = $this->getBibCallNumber($bib);
        $orders = [];
        foreach ($bib['orders'] ?? [] as $order) {
            $location = $order['location']['code'];
            $orders[$location][] = $order;
        }
        $holdingsData = [];
        if ($checkHoldings && $this->apiVersion >= 5.1) {
            $holdingsResult = $this->makeRequest(
                ['v5', 'holdings'],
                [
                    'bibIds' => $this->extractBibId($id),
                    'deleted' => 'false',
                    'suppressed' => 'false',
                    'fields' => 'fixedFields,varFields',
                ],
                'GET'
            );
            foreach ($holdingsResult['entries'] ?? [] as $entry) {
                $location = '';
                foreach ($entry['fixedFields'] as $code => $field) {
                    if (
                        (string)$code === static::HOLDINGS_LOCATION_FIELD
                        || $field['label'] === 'LOCATION'
                    ) {
                        $location = $field['value'];
                        break;
                    }
                }
                if ('' === $location) {
                    continue;
                }
                $holdingsData[$location][] = $entry;
            }
        }

        $fields = [
            'location',
            'status',
            'barcode',
            'callNumber',
            'fixedFields',
            'varFields',
        ];
        $statuses = [];
        $sort = 0;
        // Fetch hold count for items if needed:
        $displayItemHoldCount = $this->config['Holdings']['display_item_hold_counts'] ?? false;
        if ($displayItemHoldCount) {
            $fields[] = 'holdCount';
        }

        $items = $this->getItemsForBibRecord(
            $id,
            array_unique([...$this->defaultItemFields, ...$fields]),
            $patron
        );
        foreach ($items as $item) {
            $location = $this->translateLocation($item['location']);
            [$status, $duedate, $notes] = $this->getItemStatus($item);
            $available = $status == $this->mapStatusCode('-');
            // OPAC message
            if (isset($item['fixedFields']['108'])) {
                $opacMsg = $item['fixedFields']['108'];
                $trimmedMsg = trim($opacMsg['value']);
                if (strlen($trimmedMsg) && $trimmedMsg != '-') {
                    $notes[] = $this->translateOpacMessage(
                        trim($opacMsg['value'])
                    );
                }
            }
            $callnumber = isset($item['callNumber'])
                ? preg_replace('/^\|a/', '', $item['callNumber'])
                : $bibCallNumber;

            $volume = isset($item['varFields']) ? $this->extractVolume($item)
                : '';

            $entry = [
                'id' => $id,
                'item_id' => $item['id'],
                'location' => $location,
                'availability' => $available,
                'status' => $status,
                'reserve' => 'N',
                'callnumber' => $callnumber,
                'duedate' => $duedate,
                'number' => $volume,
                'barcode' => $item['barcode'],
                'sort' => $sort--,
                'requests_placed' => $displayItemHoldCount ? ($item['holdCount'] ?? null) : null,
            ];
            if ($notes) {
                $entry['item_notes'] = $notes;
            }

            if ($this->isHoldable($item, $bib)) {
                $entry['is_holdable'] = true;
                $entry['level'] = 'copy';
                $entry['addLink'] = true;
            } else {
                $entry['is_holdable'] = false;
            }

            $locationCode = $item['location']['code'] ?? '';
            if (!empty($holdingsData[$locationCode])) {
                $entry += $this->getHoldingsData($holdingsData[$locationCode]);
                $holdingsData[$locationCode]['_hasItems'] = true;
            }

            $statuses[] = $entry;
        }

        // Add holdings that don't have items
        foreach ($holdingsData as $locationCode => $holdings) {
            if (!empty($holdings['_hasItems'])) {
                continue;
            }

            $location = $this->translateLocation(
                ['code' => $locationCode, 'name' => '']
            );
            $code = $locationCode;
            while ('' === $location && $code) {
                $location = $this->getLocationName($code);
                $code = substr($code, 0, -1);
            }
            $entry = [
                'id' => $id,
                'item_id' => 'HLD_' . $holdings[0]['id'],
                'location' => $location,
                'requests_placed' => 0,
                'status' => '',
                'use_unknown_message' => true,
                'availability' => false,
                'duedate' => '',
                'barcode' => '',
                'sort' => $sort--,
            ];
            $entry += $this->getHoldingsData($holdings);

            $statuses[] = $entry;
        }

        // Add orders
        foreach ($orders as $locationCode => $orderSet) {
            $location = $this->translateLocation($orderSet[0]['location']);
            $statuses[] = [
                'id' => $id,
                'item_id' => "ORDER_{$id}_$locationCode",
                'location' => $location,
                'callnumber' => $bibCallNumber,
                'number' => '',
                'status' => $this->mapStatusCode('Ordered'),
                'reserve' => 'N',
                'item_notes' => $this->getOrderMessages($orderSet),
                'availability' => false,
                'duedate' => '',
                'barcode' => '',
                'sort' => $sort--,
            ];
        }

        usort($statuses, [$this, 'statusSortFunction']);

        if ($statuses) {
            $statuses[] = $this->getHoldingsSummary($statuses, $bib);
        }

        return $statuses;
    }

    /**
     * Check if a fine can be paid online
     *
     * @param array $fine Fine
     *
     * @return bool
     */
    protected function finePayableOnline(array $fine): bool
    {
        $code = $fine['chargeType']['code'] ?? 0;
        $desc = $fine['description'] ?? '';
        if (in_array($code, $this->onlinePayableFineTypes)) {
            return true;
        }
        foreach ($this->onlinePayableManualFineDescriptionPatterns as $pattern) {
            if (preg_match($pattern, $desc)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Get hold shelf for an available hold
     *
     * @param array $hold   Hold
     * @param array $patron The patron array from patronLogin
     *
     * @return string
     */
    protected function getHoldShelf(array $hold, array $patron): string
    {
        $location = $hold['location'];
        $cacheKey = "holdshelf|$location|" . $hold['item_id'];
        if (null !== ($shelf = $this->getCachedData($cacheKey))) {
            return $shelf;
        }

        $result = '';
        $handlerConfig = $this->getHoldShelfHandlerConfig($hold);
        if ($handlerConfig) {
            $type = $handlerConfig['type'];
            $config = $handlerConfig['config'];
            $params = $handlerConfig['params'];
            try {
                switch ($type) {
                    case 'BMA':
                        $result = $this->getHoldShelfWithBMA(
                            $config,
                            $params,
                            $hold,
                            $patron
                        );
                        break;
                    case 'IMMS':
                        $result = $this->getHoldShelfWithIMMS(
                            $config,
                            $params,
                            $hold,
                            $patron
                        );
                        break;
                    default:
                        $this->logError("Unknown hold shelf handler: $type");
                }
            } catch (\Exception $e) {
                $this->logError(
                    "Failed to get hold shelf for item {$hold['item_id']} with"
                    . " handler $type: " . (string)$e
                );
            }
        }
        $this->putCachedData(
            $cacheKey,
            $result,
            $config['locationCacheTime'] ?? 300
        );
        return $result;
    }

    /**
     * Get hold shelf handler configuration
     *
     * @param array $hold Hold
     *
     * @return ?array handler, config and params, or null if not found
     */
    protected function getHoldShelfHandlerConfig(array $hold): ?array
    {
        $location = $hold['location'];
        $handlers = $this->config['Holds']['holdShelfHandler'] ?? [];
        while ($location) {
            if ($setting = $handlers[$location] ?? '') {
                $parts = explode(':', $setting);
                $type = $parts[0];
                $config = !empty($parts[1])
                    ? ($this->config[$parts[1]] ?? null)
                    : null;
                $params = $parts[2] ?? '';
                if ($type && $config) {
                    return compact('type', 'config', 'params');
                }
            }
            $location = substr($location, 0, -1);
        }
        return null;
    }

    /**
     * Get hold shelf for an available hold with IMMS
     *
     * @param array $config IMMS configuration
     * @param array $params Extra parameters
     * @param array $hold   Hold
     * @param array $patron The patron array from patronLogin
     *
     * @return string
     */
    protected function getHoldShelfWithBMA(
        array $config,
        string $params,
        array $hold,
        array $patron
    ): string {
        foreach (['apiKey', 'url'] as $key) {
            if (empty($config[$key])) {
                $this->logError("BMA config missing $key");
                throw new ILSException('Problem with BMA configuration');
            }
        }

        $itemId = $hold['item_id'];
        if (!($barcode = $this->getItemBarcode($itemId, $patron))) {
            $this->logError("Could not retrieve barcode for item $itemId");
            return '';
        }

        $url = $config['url'] . 'reservation/apikey/company/'
            . ((int)$params) . '/null/null/null/' . urlencode($barcode)
            . '/null/null/null/null/null/null/null';
        try {
            $response = $this->httpService->get(
                $url,
                [],
                null,
                [
                    'Authorization: Bearer ' . $config['apiKey'],
                ]
            );
            if (!$response->isSuccess()) {
                throw new \Exception(
                    "BMA request $url failed: " . $response->getReasonPhrase(),
                    $response->getStatusCode()
                );
            }
            $data = json_decode($response->getBody(), true);
            $indexVar = $data['data'][0]['index_var'] ?? null;
            $indexDayId = $data['data'][0]['index_day_id'] ?? null;
            if (null === $indexVar || null === $indexDayId) {
                throw new \Exception(
                    "index_var or index_day_id not found in BMA response for $url: "
                    . $response->getBody()
                );
            }
            return "$indexVar $indexDayId";
        } catch (\Exception $e) {
            throw new ILSException("BMA request $url failed", $e->getCode(), $e);
        }
        return '';
    }

    /**
     * Get hold shelf for an available hold with IMMS
     *
     * @param array $config IMMS configuration
     * @param array $params Extra parameters
     * @param array $hold   Hold
     * @param array $patron The patron array from patronLogin
     *
     * @return string
     */
    protected function getHoldShelfWithIMMS(
        array $config,
        string $params,
        array $hold,
        array $patron
    ): string {
        foreach (['securityWsdl', 'queryWsdl', 'username', 'password'] as $key) {
            if (empty($config[$key])) {
                $this->logError("IMMS config missing $key");
                throw new ILSException('Problem with IMMS configuration');
            }
        }

        $cacheKeyToken = 'imms|' . md5(var_export($config, true));

        $itemId = $hold['item_id'];
        if (!($barcode = $this->getItemBarcode($itemId, $patron))) {
            $this->logError("Could not retrieve barcode for item $itemId");
            return '';
        }

        $shelf = '';
        try {
            if (!($authToken = $this->getCachedData($cacheKeyToken))) {
                $this->logWarning('Retrieving IMMS auth token');
                $authToken = $this->getIMMSAuthToken($config);
            }

            $client = new ProxySoapClient(
                $this->httpService,
                $config['queryWsdl'],
                $this->immsSoapOptions
            );
            try {
                $response = $client->GetItemDetails(
                    [
                        'Token' => $authToken,
                        'ItemId' => $barcode,
                    ]
                );
            } catch (\SoapFault $e) {
                if ($e->getMessage() === 'Token is invalid') {
                    // Retry with a new authentication token:
                    $this->logWarning('Refreshing IMMS auth token');
                    $authToken = $this->getIMMSAuthToken($config);
                    $response = $client->GetItemDetails(
                        [
                            'Token' => $authToken,
                            'ItemId' => $barcode,
                        ]
                    );
                } else {
                    throw new ILSException('IMMS request failed', $e->getCode(), $e);
                }
            }
            $placement = $response->ItemDetails->CurrentLocation->Placement
                ->ShortPlacementText ?? '';
            preg_match('/(\d+)/', $placement, $matches);
            $shelf = ltrim($matches[1] ?? '', '0');
        } catch (\Exception $e) {
            throw new ILSException('IMMS request failed', $e->getCode(), $e);
        }
        $this->putCachedData(
            $cacheKeyToken,
            $authToken,
            $config['authTokenCacheTime'] ?? 3600
        );
        return $shelf;
    }

    /**
     * Get a new authentication token from IMMS
     *
     * @param array $config IMMS config
     *
     * @return string
     */
    protected function getIMMSAuthToken(array $config): string
    {
        $client = new ProxySoapClient(
            $this->httpService,
            $config['securityWsdl'],
            $this->immsSoapOptions
        );

        $response = $client->Login(
            [
                'Username' => $config['username'],
                'Password' => $config['password'],
            ]
        );
        return (string)$response->Token;
    }

    /**
     * Get item barcode
     *
     * @param string $itemId Item ID
     * @param array  $patron The patron array from patronLogin
     *
     * @return string
     */
    protected function getItemBarcode(string $itemId, array $patron): string
    {
        // Get item barcode using same request as elsewhere for cacheability
        $item = $this->makeRequest(
            [$this->apiBase, 'items', $itemId],
            ['fields' => 'bibIds,varFields'],
            'GET',
            $patron
        );
        foreach ($item['varFields'] ?? [] as $field) {
            if ('b' === $field['fieldTag']) {
                return $field['content'];
            }
        }
        return '';
    }

    /**
     * Make Request
     *
     * Makes a request to the Sierra REST API
     *
     * Finna: Adds caching for bibs and items
     *
     * @param array  $hierarchy    Array of values to embed in the URL path of the
     * request
     * @param array  $params       A keyed array of query data
     * @param string $method       The http request method to use (Default is GET)
     * @param array  $patron       Patron information, if available
     * @param bool   $returnStatus Whether to return HTTP status code and response
     * as a keyed array instead of just the response
     *
     * @throws ILSException
     * @return mixed JSON response decoded to an associative array, an array of HTTP
     * status code and JSON response when $returnStatus is true or null on
     * authentication error when using patron-specific access
     */
    protected function makeRequest(
        $hierarchy,
        $params = false,
        $method = 'GET',
        $patron = false,
        $returnStatus = false
    ) {
        $url = $this->getApiUrlFromHierarchy($hierarchy);
        // Allow caching of GET requests for bibs and items:
        $bibsUrl = $this->getApiUrlFromHierarchy([$this->apiBase, 'bibs']);
        $itemsUrl = $this->getApiUrlFromHierarchy([$this->apiBase, 'items']);
        $cacheKey = null;
        if (
            'GET' === $method
            && (strncmp($url, $bibsUrl, strlen($bibsUrl)) === 0
            || strncmp($url, $itemsUrl, strlen($itemsUrl)) === 0)
        ) {
            // Cacheable request, check cache:
            $paramArray = compact('params', 'method', 'patron', 'returnStatus');
            $cacheKey = "request|$url|" . md5(var_export($paramArray, true));
            if (null !== ($result = $this->getCachedData($cacheKey))) {
                return $result;
            }
        }
        $result = parent::makeRequest(
            $hierarchy,
            $params,
            $method,
            $patron,
            $returnStatus
        );
        if ($cacheKey) {
            // Cache records by default for 300 seconds:
            $this->putCachedData(
                $cacheKey,
                $result,
                $this->config['Catalog']['request_cache_time'] ?? 300
            );
        }
        return $result;
    }

    /**
     * Get locations
     *
     * @return array
     */
    protected function getLocations(): array
    {
        // Ensure cache:
        $this->getLocationName('*');
        $locations = $this->getCachedData('locations');
        if (null === $locations) {
            throw new \Exception('Location cache not available');
        }
        return $locations;
    }

    /**
     * Build an API URL from a hierarchy array
     *
     * @param array $hierarchy Hierarchy
     *
     * @return string
     */
    protected function getApiUrlFromHierarchy(array $hierarchy): string
    {
        $url = $this->config['Catalog']['host'];
        foreach ($hierarchy as $value) {
            if (is_array($value)) {
                if ('encoded' === $value['type']) {
                    $url .= '/' . $value['value'];
                    continue;
                }
                $value = $value['value'];
            } else {
                $url .= '/' . urlencode($value);
            }
        }
        return $url;
    }
}
