<?php

namespace StripeIntegration\Payments\Api;

interface ServiceInterface
{
    /**
     * Returns Redirect Url
     *
     * @api
     * @return string Redirect Url
     */
    public function redirect_url();

    /**
    * Gets the created payment intent at the checkout
    *
    * @api
    *
    * @return mixed Json object containing the new PI ID.
    */
    public function get_payment_intent();

    /**
    * Creates a fresh SetupIntent and returns the client secret
    *
    * @api
    *
    * @return string|null $setupIntentClientSecret
    */
    public function get_setup_intent();

    /**
     * Estimate Shipping by Address
     *
     * @api
     * @param mixed $address
     *
     * @return string
     */
    public function estimate_cart($address);

    /**
     * Set billing address from data object
     *
     * @api
     * @param mixed $data
     *
     * @return string
     */
    public function set_billing_address($data);

    /**
     * Apply Shipping Method
     *
     * @api
     * @param mixed $address
     * @param string|null $shipping_id
     *
     * @return string
     */
    public function apply_shipping($address, $shipping_id = null);

    /**
     * Place Order
     *
     * @api
     * @param mixed $result
     *
     * @return string
     */
    public function place_order($result);

    /**
     * Add to Cart
     *
     * @api
     * @param string $request
     * @param string|null $shipping_id
     *
     * @return string
     */
    public function addtocart($request, $shipping_id = null);

    /**
     * Get Cart Contents
     *
     * @api
     * @return string
     */
    public function get_cart();

    /**
     * Get PR API params to initialize Stripe Express buttons
     *
     * @api
     * @param string $type
     *
     * @return mixed Json object with params
     */
    public function get_prapi_params($type);

    /**
    * Creates a Klarna Source object through the Stripe API
    *
    * @api
    * @param mixed $billingAddress
    * @param mixed $shippingAddress
    * @param string|null $shippingMethod
    * @param string|null $guestEmail
    * @param string|null $sourceId
    *
    * @return mixed Json object with payment options data necessary to render the payment form.
    */
    public function get_klarna_payment_options($billingAddress, $shippingAddress = null, $shippingMethod = null, $guestEmail = null, $sourceId = null);
}
