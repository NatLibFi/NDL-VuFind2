<?php
// @codingStandardsIgnoreStart
namespace Finna\OnlinePayment\Handler\Connector\Cpu;

/**
 * Product data wrapper to make it easier to use and validate products.
 *
 * @since 2015-05-19 MB, version 1.0 created
 * @version 1.0
 *
 */
class Product
{
    /**
     * Product code. Max. length 25 chars.
     *
     * Use sku or other identification.
     * There needs to be a product with this sku in eCommerce!
     *
     * @var string
     */
    public $Code = null;

    /**
     * Order amount.
     *
     * @var integer
     */
    public $Amount = null;

    /**
     * Price of single product vat included in cents.
     *
     * @example 20.50€ = 2050
     * @var integer
     */
    public $Price = null;

    /**
     * Product description. Max. length 40 chars.
     * Will be added into confirmation email sent by server at checkout.
     *
     * @var string
     */
    public $Description = null;

    /**
     * Vat code to be used with this product. Max. length 25 chars.
     * There needs to be a taxcode with this taxcode in eCommerce!
     *
     * @var string
     */
    public $Taxcode = null;

    /**
     * Constructor creates the product.
     * Sanitizes all the parameters to be fit for Product object.
     *
     * @param string $code Product code
     * @param integer $amount Amount ordered
     * @param number $price Price of single product
     * @param string $description Short product description
     */
    public function __construct($code, $amount = null, $price = null, $description = null)
    {
        $this->Code = Client::sanitize($code);

        if ($amount) {
            $this->Amount = Client::sanitize($amount);
        }

        if ($price) {
            $this->Price = Client::sanitize($price);
        }

        if ($description) {
            $this->Description = Client::sanitize($description);
        }
    }

    /**
     * Checks mandatory properties of Product.
     *
     * @return boolean All mandatory properties are set
     */
    public function isValid()
    {
        return ($this->Code != null)
            ? true
            : false;
    }
}
// @codingStandardsIgnoreEnd
