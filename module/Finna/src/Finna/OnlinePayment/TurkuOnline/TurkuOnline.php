<?php

namespace Finna\OnlinePayment\TurkuOnline;

use Finna\OnlinePayment\Paytrail\PaytrailE2;
use \DateTime;

class TurkuOnline extends PaytrailE2 {
    
    use \Finna\OnlinePayment\OnlinePaymentModuleTrait;

    protected $company = "finnatesti";

    protected $authType = "sha256";

    protected $oId = 'ANONYYMI';

    protected $timeStamp = "";

    protected $description = "Finna testaa";

    protected $products = [];

    protected $hashedAuth = null;

    protected $requestBody = "";

    public function setOid($oId) {
        $this->oId = $oId;
    }

    public function getOid() {
        return $this->oId;
    }

    public function getTimeStamp() {
        return $this->timeStamp;
    }

    public function setTransaction($transaction) {
        $this->transaction = $transaction;
    }

    public function generateTimeStamp() {
        $this->timeStamp = gmdate("Y-m-d\Th:i:s\Z");
    }

    public function generateHeaders() {
        $this->generateHash();
        return [
            'Authorization' => $this->hashedAuth,
            'X-TURKU-TS' => $this->timeStamp,
            'charset' => 'utf-8',
            'X-TURKU-SP' => 'finna',
            'X-MERCHANT-ID' => $this->merchantId,
            'X-TURKU-OID' => "ANONYYMI"
        ];
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
        $this->products[] = [
            "title" => substr($name, 0, 255),
            "code" => $code,
            "sapCode" => '000000000000010601',
            "amount" => $quantity,
            "price" => number_format($unitPrice / 100, 2, '.', ''),
            "vat" => "23.00",
            "discount" => "0.00",
            "type" => $type
        ];
    }

    public function sendRequest($url) {
        $this->generateTimeStamp();
        $this->requestBody = json_encode($this->generateBody());
        $headers = $this->generateHeaders();
        $response = $this->postRequest($url, $this->requestBody, [], $headers);
        echo "<pre>";
        var_dump($response);
        echo "</pre>";
    }

    public function generateBody() {
        if (null === $this->orderNumber) {
            throw new \Exception('Order number must be specified');
        }
        if (null === $this->totalAmount && empty($this->products)) {
            throw new \Exception(
                'Either total amount or products must be specified'
            );
        }
        if (null !== $this->totalAmount && !empty($this->products)) {
            throw new \Exception(
                'Total amount and products can not be used at the same time'
            );
        }

        return [
            'payment' => [
                'orderNumber' => $this->orderNumber,
                'locale' => $this->locale,
                'currency' => 'EUR',
                'urlSet' => [
                    'success' => $this->successUrl,
                    'failure' => $this->cancelUrl,
                    'pending' => '',
                    'notification' => $this->notifyUrl
                ],
                'orderDetails' => [
                    'includeVat' => "0",
                    'products' => [
                        $this->products
                    ]
                ]
                
            ]
        ];
    }

    public function tryRequest($body, $headers) {

    }

    public function getProductAsArray() {

    }

    public function generateHash() {
        $this->hashedAuth = hash('sha256', "finna" . $this->timeStamp . $this->requestBody . $this->secret);
    }
}

?>