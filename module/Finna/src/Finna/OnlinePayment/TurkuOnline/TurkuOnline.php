<?php

namespace Finna\OnlinePayment\TurkuOnline;

use Finna\OnlinePayment\Paytrail\PaytrailE2;
use \DateTime;

class TurkuOnline extends PaytrailE2 {
    
    use \Finna\OnlinePayment\OnlinePaymentModuleTrait;

    protected $company = "Finna (Auroran maksut)";

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
            'X-MERCHANT-ID' => 'TURKU',
            'X-TURKU-SP' => $this->company,
            'X-TURKU-TS' => $this->timeStamp,
            'X-TURKU-OID' => 'ANONYYMI',
            'Authorization' => $this->hashedAuth
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
            "sapCode" => '000000000000011151',
            "amount" => $quantity,
            "price" => number_format($unitPrice / 100, 2, '.', ''),
            "vat" => "23.00",
            "discount" => "0.00",
            "type" => '1'
        ];
    }

    public function sendRequest($url) {
        $this->generateTimeStamp();
        $this->requestBody = json_encode($this->generateBody());
        $headers = $this->generateHeaders();
        $response = $this->postRequest($url, $this->requestBody, [], $headers);
        if (isset($response['httpCode']) && $response['httpCode'] === 200) {
            $responseArray = json_decode($response['response'], true);
            if (isset($responseArray['orderNumber']) && isset($responseArray['url'])) {
                header('Location:' . $responseArray['url']); // Lets see if this works
                exit();
            }
        } else {
            var_dump("Failed");
        }
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
                    'includeVat' => '0',
                    'products' => $this->products
                ]
                
        ];
    }

    public function generateHash() {
        $this->hashedAuth = hash('sha256', $this->company . $this->timeStamp . $this->requestBody . $this->secret);
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
    public function validateRequest($orderNumber, $paid, $timeStamp, $method,
        $authCode
    ) {
        $response = "$orderNumber|$timeStamp|$paid|$method|{$this->secret}";
        $hash = md5($response);
        return $hash;
    }
}

?>