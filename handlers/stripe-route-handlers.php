<?php
namespace Mcms\Api\StripeRestRouteHandlers;
use Stripe\Exception\ApiErrorException;
use \WP_REST_Response;
use \WP_REST_Request;

class StripeRestRouteHandlers {
	public $stripeSecretKey;
	public $stripe;
	public $gateway;

	public $mode;

	public $webhookKey;

	public $publicKey;

	public function __construct() {
		$stripeApiSettings = get_option('woocommerce_stripe_api_settings');
		$mode = $stripeApiSettings['mode'];
		$this->mode = $mode;
		$stripeSecretKey = $stripeApiSettings["secret_key_{$mode}"];
		$this->webhookKey = $stripeApiSettings["webhook_secret_{$mode}"];
//		error_log(print_r($this->webhookKey, true));
		$this->publicKey = $stripeApiSettings["publishable_key_{$mode}"];
		$this->stripeSecretKey = $stripeSecretKey;
		$this->gateway = new \WC_Stripe_Gateway();
		$this->stripe = new \Stripe\StripeClient($this->stripeSecretKey);
	}

	public function createProduct($productName, $price, $metadata = [], $currency = 'EUR') {
		$product = $this->stripe->products->create([
			'name' => $productName,
			'metadata' => $metadata, // Add metadata here
		]);

		$this->stripe->prices->create([
			'unit_amount' => $price * 100, // Stripe expects the price in cents
			'currency' => $currency,
			'product' => $product->id,
		]);

		return $product;
	}

	function getProduct($name) {
		$products = $this->stripe->products->all();

		foreach ($products->data as $product) {
			if ($product->name == $name) {
				$product->price = $this->getPricesForProduct($product->id);
				return $product;
			}
		}
		return null;
	}

	function getPricesForProduct($productId) {
		// Retrieve all prices
		$prices = $this->stripe->prices->all(['product' => $productId]);

		return count($prices->data) > 0 ? $prices->data[0] : null;
	}

	public function getReturnUrl() {
		return get_field('site-url', 'option');
	}
}


/**
 * This method only works with embedded checkout
 * @param WP_REST_Request $request
 *
 * @return WP_REST_Response
 * @throws ApiErrorException
 */
function create_checkout_session(WP_REST_Request $request) {

	$stripe = new StripeRestRouteHandlers();

	$products = [];
	foreach (WC()->cart->get_cart() as $cartItem) {
		$product = $stripe->getProduct($cartItem['data']->get_name());

		if (!$product) {
			$product = $stripe->createProduct($cartItem['data']->get_name(), $cartItem['data']->get_price(),
				[
					'product_id' => $cartItem['data']->get_id(),
					'product_sku' => $cartItem['data']->get_sku(),
				]);
		}


		$products[] = [
			'price' => $product->price->id,
			'quantity' => $cartItem['quantity'],
		];
	}
	$totals = WC()->cart->get_totals();
	$shipping = $totals['shipping_total'];
	$products[] = [
		'price_data' => [
			'currency' => 'eur',
			'product_data' => [
				'name' => 'Shipping',
			],
			'unit_amount' => $shipping * 100,
		],
		'quantity' => 1,
	];


//	error_log(print_r($stripe->getReturnUrl(), true));
//	error_log(print_r($gateway->get_api_options($mode), true));



	$orderId = WC()->session->get('order_awaiting_payment');
	$order = wc_get_order($orderId);
	$orderIsValid = ($order && is_a($order, 'WC_Order') && $order->get_status() === 'pending');
	$checkout = new \WC_Checkout();
	$is_new_order = false;


	if (!$orderId || !$orderIsValid) {
		$orderId = $checkout->create_order([
			'payment_method' => 'stripe_cc', 'payment_method_title' => 'Credit Card', 'set_paid' => false,
			'billing' => WC()->customer->get_billing(),
			'shipping' => WC()->customer->get_shipping(),
			'origin' => 'direct',
		]);

		$order = wc_get_order($orderId);

		WC()->session->set( 'order_awaiting_payment', $orderId );
		WC()->session->save_data();
		$is_new_order = true;
	}

	$customer = findOrCreateCustomer($request);

	$checkout_session = $stripe->stripe->checkout->sessions->create([
		'ui_mode' => 'embedded',
		'line_items' => $products,
		'customer' => $customer->id,
		'mode' => 'payment',
		'metadata' => [
			'order_id' => $orderId,
			'webhook_id' => "webhook_id_{$stripe->mode}",
		],
//		'customer_email'=> $customer ? null : WC()->customer->get_email(),
		'return_url' => $stripe->getReturnUrl() . '/thank-you?session_id={CHECKOUT_SESSION_ID}',
	]);


	if ($is_new_order) {
		$order->update_meta_data(\WC_Stripe_Constants::PAYMENT_INTENT_ID , $checkout_session->id);
		$order->update_meta_data(\WC_Stripe_Constants::STRIPE_MANDATE , false);
		$order->save();
	}


	return new WP_REST_Response([
		'clientSecret' => $checkout_session->client_secret,
		'orderId' => $orderId,
		'intent' => $checkout_session,
	],
	);
}

function findOrCreateCustomer(WP_REST_Request $request) {
	$stripe = new StripeRestRouteHandlers();

	$customer = $stripe->stripe->customers->search([
		'query' => "email:'" . WC()->customer->get_email() . "' AND metadata['customer_id']:'" . WC()->customer->get_id() . "'",
	]);

	if ($customer && count($customer->data) === 1) {
		return $customer->data[0];
	}

	$customer = $stripe->stripe->customers->create([
		'email' => WC()->customer->get_email(),
		'name' => WC()->customer->get_first_name() . ' ' . WC()->customer->get_last_name(),
		'metadata' => [
			'customer_id' => WC()->customer->get_id(),
		],
	]);

	return $customer;

}

function get_or_create_product($stripe, $productName, $metadata) {
	// Retrieve all products
	$products = $stripe->products->all();

	// Check if product exists
	foreach ($products->data as $product) {
		if ($product->name == $productName) {
			// Product exists, return it
			return $product;
		}
	}

	// Product doesn't exist, create a new one
	$newProduct = $stripe->products->create([
		'name' => $productName,
		'metadata' => $metadata, // Add metadata here
	]);

	return $newProduct;
}

function createUser() {

}

/**
 * @return WP_REST_Response {
 * @type string $id
 * @type string $object
 * @type mixed $after_expiration
 * @type mixed $allow_promotion_codes
 * @type int $amount_subtotal
 * @type int $amount_total
 * @type array {
 * @type bool $enabled
 * @type mixed $liability
 * @type mixed $status
 *     } $automatic_tax
 * @type mixed $billing_address_collection
 * @type mixed $cancel_url
 * @type mixed $client_reference_id
 * @type mixed $client_secret
 * @type mixed $consent
 * @type mixed $consent_collection
 * @type int $created
 * @type string $currency
 * @type mixed $currency_conversion
 * @type array $custom_fields
 * @type array {
 * @type mixed $after_submit
 * @type mixed $shipping_address
 * @type mixed $submit
 * @type mixed $terms_of_service_acceptance
 *     } $custom_text
 * @type string $customer
 * @type string $customer_creation
 * @type array {
 * @type array {
 * @type mixed $city
 * @type string $country
 * @type mixed $line1
 * @type mixed $line2
 * @type mixed $postal_code
 * @type mixed $state
 *         } $address
 * @type string $email
 * @type string $name
 * @type mixed $phone
 * @type string $tax_exempt
 * @type array $tax_ids
 *     } $customer_details
 * @type string $customer_email
 * @type int $expires_at
 * @type mixed $invoice
 * @type array {
 * @type bool $enabled
 * @type array {
 * @type mixed $account_tax_ids
 * @type mixed $custom_fields
 * @type mixed $description
 * @type mixed $footer
 * @type mixed $issuer
 * @type array $metadata
 * @type mixed $rendering_options
 *         } $invoice_data
 *     } $invoice_creation
 * @type bool $livemode
 * @type mixed $locale
 * @type array $metadata
 * @type string $mode
 * @type string $payment_intent
 * @type mixed $payment_link
 * @type string $payment_method_collection
 * @type array {
 * @type string $id
 * @type string $parent
 *     } $payment_method_configuration_details
 * @type array {
 * @type array {
 * @type string $request_three_d_secure
 *         } $card
 *     } $payment_method_options
 * @type array $payment_method_types
 * @type string $payment_status
 * @type array {
 * @type bool $enabled
 *     } $phone_number_collection
 * @type mixed $recovered_from
 * @type string $redirect_on_completion
 * @type string $return_url
 * @type mixed $setup_intent
 * @type mixed $shipping
 * @type mixed $shipping_address_collection
 * @type array $shipping_options
 * @type mixed $shipping_rate
 * @type string $status
 * @type mixed $submit_type
 * @type mixed $subscription
 * @type mixed $success_url
 * @type array {
 * @type int $amount_discount
 * @type int $amount_shipping
 * @type int $amount_tax
 *     } $total_details
 * @type string $ui_mode
 * @type mixed $url
 * }
 * @throws ApiErrorException
 */
function processStatus(WP_REST_Request $request) {
	$sessionId = $request->get_param('session_id');
	$stripe = new StripeRestRouteHandlers();

	$session = $stripe->stripe->checkout->sessions->retrieve($sessionId);

//	error_log(print_r($session, true));

	if ($session->payment_status === 'paid' && $session->status === 'complete') {
		$order = wc_get_order($session->metadata->order_id);
		$order->update_status('completed');
		$order->add_order_note( __( 'Stripe payment complete', 'mcms' ) );
		WC()->session->set( 'order_awaiting_payment', null );
	}

	if ($session->payment_status === 'unpaid' && $session->status === 'incomplete') {
		$order = wc_get_order($session->metadata->order_id);
		$order->update_status('failed');
		$order->add_order_note( __( 'Stripe payment failed', 'mcms' ) );
	}


	return new WP_REST_Response([
		'status' => $session->status,
		'payment_status' => $session->payment_status,
		// convert timestamp to date
		'created' => date('Y-m-d H:i:s', $session->created),
		'id' => $order->get_id()
	]);
}

function webhook(WP_REST_Request $request) {
/*	$res = new \WC_Stripe_Controller_Webhook();
	$payload         = $request->get_body();
	$json_payload    = json_decode( $payload, true );

	$header          = isset( $_SERVER['HTTP_STRIPE_SIGNATURE'] ) ? $_SERVER['HTTP_STRIPE_SIGNATURE'] : '';
	$stripe = new StripeRestRouteHandlers();

	$res = $res->webhook($request);
	$event = \Stripe\Webhook::constructEvent( $payload, $header, $stripe->webhookKey, apply_filters( 'wc_stripe_webhook_signature_tolerance', 600 ) );
	wc_stripe_process_payment_intent_succeeded($event->data->object, $request);*/


}