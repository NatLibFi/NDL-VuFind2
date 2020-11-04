<?php
/**
 * Paytrail sales-channel client
 *
 * PHP version 7
 *
 * Copyright (C) The National Library of Finland 2020.
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
 * @author   Juha Luoma <juha.luoma@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/vufind2:developer_manual Wiki
 * @link     http://docs.paytrail.com/ Paytrail API documentation
 */
namespace Finna\OnlinePayment\Paytrail;

/**
 * Paytrail sales-channel client
 *
 * @category VuFind
 * @package  OnlinePayment
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @author   Juha Luoma <juha.luoma@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/vufind2:developer_manual Wiki
 * @link     http://docs.paytrail.com/ Paytrail API documentation
 */
class PaytrailChannel extends \Finna\OnlinePayment\Paytrail\PaytrailE2
{
    use \Finna\OnlinePayment\OnlinePaymentModuleTrait;

    /**
     * Channel ID
     *
     * @var string
     */
    protected $channelId;

    /**
     * Contacts email
     *
     * @var string
     */
    protected $contactEmail = '';

    /**
     * Contacts first name
     *
     * @var string
     */
    protected $contactFirstName = '';

    /**
     * Contacts last name
     *
     * @var string
     */
    protected $contactLastName = '';

    /**
     * Contacts street address
     *
     * @var string
     */
    protected $contactAddrStreet = '';

    /**
     * Contacts ZIP code
     *
     * @var string
     */
    protected $contactZipCode = '';

    /**
     * Contact country {fi, FI, se, SE}
     *
     * @var string
     */
    protected $contactAddrCountry = '';

    /**
     * Contact city
     *
     * @var string
     */
    protected $contactAddrCity = '';

    /**
     * Include vat
     *
     * @var int
     */
    protected $includeVat = 0;

    /**
     * Constructor
     *
     * @param string $channelId Merchant ID
     * @param string $secret    Merchant secret
     * @param string $locale    Locale
     */
    public function __construct($channelId, $secret, $locale)
    {
        if (!in_array($locale, ['fi_FI', 'sv_SE', 'en_US'])) {
            throw new \Exception("Invalid locale: $locale");
        }

        $this->channelId = $channelId;
        $this->secret = $secret;
        $this->locale = $locale;
    }

    /**
     * Set URLs
     *
     * @param string $successUrl Success URL
     * @param string $cancelUrl  Cancel/failure URL
     * @param string $notifyUrl  Notification URL
     *
     * @return void
     */
    public function setUrls($successUrl, $cancelUrl, $notifyUrl)
    {
        $this->successUrl = $successUrl;
        $this->cancelUrl = $cancelUrl;
        $this->notifyUrl = $notifyUrl;
    }

    /**
     * Set contact
     *
     * @param array $contact data
     *
     * @return void
     */
    public function setContact($contact)
    {
        $this->contactEmail = $contact['email'];
        $this->contactFirstName = $contact['firstname'];
        $this->contactLastName = $contact['lastname'];
        $this->contactAddrStreet = $contact['street'];
        $this->contactAddrCity = $contact['city'];
        $this->contactAddrCountry = $contact['country'];
        $this->contactZipCode = $contact['zip'];
    }

    /**
     * Add a product
     *
     * @param string $name       Product name
     * @param string $code       Product code
     * @param int    $quantity   Number of items
     * @param int    $unitPrice  Unit price in cents
     * @param int    $vatPercent VAT percent
     * @param int    $type       Payment type (const TYPE_*)
     *
     * @return void
     */
    public function addProduct($name, $code, $quantity, $unitPrice, $vatPercent,
        $type
    ) {
        $index = count($this->products);
        // For some reason the E2 interface does not allow alphanumeric item codes
        if ($code) {
            $name = "$code $name";
        }
        $name = preg_replace(
            '/[^\pL-0-9- "\',()\[\]{}*\/+\-_,.:&!?@#$£=*;~]+/u', ' ', $name
        );

        // Payment channel specifies that if value is not sent, leave it empty
        $this->products[] = [
            "ITEM_TITLE[$index]" => mb_substr($name, 0, 255),
            "ITEM_NO[$index]" => '',
            "ITEM_AMOUNT[$index]" => $quantity,
            "ITEM_PRICE[$index]" => number_format($unitPrice / 100, 2, '.', ''),
            "ITEM_TAX[$index]" => $vatPercent,
            "ITEM_MERCHANT_ID[$index]" => $code,
            "ITEM_CP[$index]" => '1',
            "ITEM_DISCOUNT[$index]" => '',
            "ITEM_TYPE[$index]" => ''
        ];
    }

    /**
     * Create payment form data
     *
     * @return array Form fields
     */
    public function createPaymentFormData()
    {
        if (null === $this->orderNumber) {
            throw new \Exception('Order number must be specified');
        }
        if (empty($this->products)) {
            throw new \Exception('Channelpayment must have products');
        }
        $amount = count($this->products);
        $request = [
            'CHANNEL_ID' => $this->channelId,
            'ORDER_NUMBER' => $this->orderNumber,
            'CURRENCY' => $this->currency,
            'RETURN_ADDRESS' => $this->successUrl,
            'CANCEL_ADDRESS' => $this->cancelUrl,
            'NOTIFY_ADDRESS' => $this->notifyUrl,
            'VERSION' => "1",
            'CULTURE' => $this->locale,
            'PRESELECTED_METHOD' => '',
            'CONTACT_TELNO' => '',
            'CONTACT_CELLNO' => '',
            'CONTACT_EMAIL' => $this->contactEmail,
            'CONTACT_FIRSTNAME' => $this->contactFirstName,
            'CONTACT_LASTNAME' => $this->contactLastName,
            'CONTACT_COMPANY' => '',
            'CONTACT_ADDR_STREET' => $this->contactAddrStreet,
            'CONTACT_ADDR_ZIP' => $this->contactZipCode,
            'CONTACT_ADDR_CITY' => $this->contactAddrCity,
            'CONTACT_ADDR_COUNTRY' => $this->contactAddrCountry,
            'INCLUDE_VAT' => $this->includeVat,
            'ITEMS' => $amount
        ];

        foreach ($this->products as $product) {
            $request += $product;
        }

        // AUTHCODE
        $authFields = array_values($request);
        array_unshift($authFields, $this->secret);
        $request['AUTHCODE'] = strtoupper(hash('md5', implode('|', $authFields)));

        return $request;
    }

    /**
     * Validate payment return and notify requests.
     *
     * @param string $orderNumber Order number
     * @param string $paymentId   Payment signature
     * @param int    $timeStamp   Timestamp
     * @param string $status      Payment status
     * @param string $authCode    Returned authentication code
     *
     * @return bool
     */
    public function validateRequest($orderNumber, $paymentId, $timeStamp, $status,
        $authCode
    ) {
        $response = "$orderNumber|$paymentId|$timeStamp|$this->secret";
        $hash = strtoupper(hash('md5', $response));
        return $authCode === $hash;
    }
}
