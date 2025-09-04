<?php

/**
 * Common functionality for container record formats.
 *
 * PHP version 8
 *
 * Copyright (C) The National Library of Finland 2022-2025.
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
 * @package  RecordDrivers
 * @author   Aleksi Peebles <aleksi.peebles@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:record_drivers Wiki
 */

namespace Finna\ILS\Driver\Feature;

use Stringable;

/**
 * Common functionality for ILS drivers.
 *
 * @category VuFind
 * @package  RecordDrivers
 * @author   Aleksi Peebles <aleksi.peebles@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:record_drivers Wiki
 */
trait FinnaCommonILSTrait
{
    /**
     * Array containing definitions for default email messaging service settings
     *
     * @var array
     */
    protected array $defaultEmailMessagingServices = [
        'pickUpNotice' => [
            'active' => false,
            'type' => 'pickUpNotice',
            'sendMethods' => [
                'letter' => [
                    'method' => 'letter',
                    'active' => false,
                    'type' => 'letter',
                ],
                'email' => [
                    'method' => 'email',
                    'active' => false,
                    'type' => 'email',
                ],
                'sms' => [
                    'method' => 'sms',
                    'active' => false,
                    'type' => 'sms',
                ],
                'none' => [
                    'method' => 'none',
                    'active' => false,
                    'type' => 'none',
                ],
            ],
        ],
        'overdueNotice' => [
            'active' => false,
            'type' => 'overdueNotice',
            'sendMethods' => [
                'letter' => [
                    'method' => 'letter',
                    'active' => false,
                    'type' => 'letter',
                ],
                'email' => [
                    'method' => 'email',
                    'active' => false,
                    'type' => 'email',
                ],
                'sms' => [
                    'method' => 'sms',
                    'active' => false,
                    'type' => 'sms',
                ],
                'none' => [
                    'method' => 'none',
                    'active' => false,
                    'type' => 'none',
                ],
            ],
        ],
        'dueDateAlert' => [
            'active' => false,
            'type' => 'dueDateAlertEmail',
            'sendMethods' => [
                'email' => [
                    'method' => 'email',
                    'active' => false,
                    'type' => 'email',
                ],
                'none' => [
                    'method' => 'none',
                    'active' => false,
                    'type' => 'none',
                ],
            ],
        ],
    ];

    /**
     * Array containing settings for common driver messaging services
     *
     * @var array
     */
    protected array $defaultDriverMessagingServices = [
        'pickUpNotice' => [
            'type' => 'pickUpNotice',
            'settings' => [
                'transport_types' => [
                    'type' => 'select',
                    'options' => [
                        'email' => [
                            'active' => false,
                        ],
                        'print' => [
                            'active' => false,
                        ],
                        'sms' => [
                            'active' => false,
                        ],
                    ],
                ],
            ],
        ],
        'overdueNotice' => [
            'type' => 'overdueNotice',
            'settings' => [
                'transport_types' => [
                    'type' => 'select',
                    'options' => [
                        'email' => [
                            'active' => false,
                        ],
                        'print' => [
                            'active' => false,
                        ],
                        'sms' => [
                            'active' => false,
                        ],
                    ],
                ],
            ],
        ],
        'dueDateAlert' => [
            'type' => 'dueDateAlert',
            'settings' => [
                'transport_types' => [
                    'type' => 'select',
                    'options' => [
                        'email' => [
                            'active' => false,
                        ],
                        'inactive' => [
                            'active' => false,
                        ],
                        'sms' => [
                            'active' => false,
                        ],
                    ],
                ],
                'days_in_advance' => [
                    'type' => 'select',
                    'value' => '',
                    'options' => [
                        1 => [
                            'name' => 'messaging_settings_num_of_days',
                            'active' => true,
                        ],
                        2 => [
                            'name' => 'messaging_settings_num_of_days_plural',
                            'active' => false,
                        ],
                        3 => [
                            'name' => 'messaging_settings_num_of_days_plural',
                            'active' => false,
                        ],
                        4 => [
                            'name' => 'messaging_settings_num_of_days_plural',
                            'active' => false,
                        ],
                        5 => [
                            'name' => 'messaging_settings_num_of_days_plural',
                            'active' => false,
                        ],
                    ],
                    'readonly' => false,
                ],
            ],
        ],
    ];

    /**
     * Create a profile array according to getMyProfile specs defined in the documentation.
     * Each value is trimmed if they are not null.
     *
     * @param Stringable|string|null $firstname         Profile first name
     * @param Stringable|string|null $lastname          Profile last name
     * @param string                 $birthdate         Y-m-d or an empty string
     * @param Stringable|string|null $address1          Address 1
     * @param Stringable|string|null $address2          Address 2
     * @param Stringable|string|null $city              City
     * @param Stringable|string|null $country           Country
     * @param Stringable|string|null $zip               Postal code
     * @param Stringable|string|null $phone             Phone number
     * @param Stringable|string|null $mobile_phone      Mobile phone number
     * @param Stringable|string|null $expiration_date   Profile expiration date
     * @param Stringable|string|null $group             Group i.e. Student, Staff, Faculty, etc
     * @param Stringable|string|null $home_library      The locationID value of a pick-up location
     *                                                  (see getPickUpLocations) that should be
     *                                                  used as the patron's default
     * @param array                  $nonDefaultFields  Non default fields not documented in the documentation.
     *                                                  Merges into the resulting profile array.
     * @param array                  $messagingServices [Finna] Array containing information about
     *                                                  users messaging services.
     *                                                  See $defaultDriverMessagingServices,
     *                                                  $defaultEmailMessagingServices
     * @param ?string                $loan_history      [Finna] Does the user have loan history enabled in the ILS?
     * @param Stringable|string|null $email             [Finna] The profile's email address (null if unavailable)
     *
     * @see https://vufind.org/wiki/development:plugins:ils_drivers#getmyprofile
     *
     * @return array
     */
    protected function createProfileArray(
        Stringable|string|null $firstname = null,
        Stringable|string|null $lastname = null,
        string $birthdate = '',
        Stringable|string|null $address1 = null,
        Stringable|string|null $address2 = null,
        Stringable|string|null $city = null,
        Stringable|string|null $country = null,
        Stringable|string|null $zip = null,
        Stringable|string|null $phone = null,
        Stringable|string|null $mobile_phone = null,
        Stringable|string|null $expiration_date = null,
        Stringable|string|null $group = null,
        Stringable|string|null $home_library = null,
        array $nonDefaultFields = [],
        array $messagingServices = [],
        ?string $loan_history = null,
        Stringable|string|null $email = null
    ): array {
        $nonDefaultFields = array_merge(
            $nonDefaultFields,
            compact('messagingServices', 'loan_history', 'email')
        );
        return parent::createProfileArray(
            $firstname,
            $lastname,
            $birthdate,
            $address1,
            $address2,
            $city,
            $country,
            $zip,
            $phone,
            $mobile_phone,
            $expiration_date,
            $group,
            $home_library,
            $nonDefaultFields
        );
    }
}
