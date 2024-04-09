<?php
namespace Mcms\Api\StripeRestRouteHandlers;
use \WP_REST_Response;
use \WP_REST_Request;

class StripeRestRouteHandlers {
	public $stripeSecretKey;
	public $stripe;

	public function __construct($stripeSecretKey) {
		$this->stripeSecretKey = $stripeSecretKey;
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
}



function create_checkout_session(WP_REST_Request $request) {
	$stripeSecretKey = 'sk_test_51MGcwsFVnCuD42ua5r8YzwBOO3nrFd9EPlpX5Qd9BVNmK0aXFUQiUP007o9e38oQonPgehh7FbbidoauVG5ck6xL003qCZu6b2';
	$YOUR_DOMAIN = 'http://localhost:5173';
	$stripe = new StripeRestRouteHandlers($stripeSecretKey);

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

//	print_r($products);

	$checkout_session = $stripe->stripe->checkout->sessions->create([
		'ui_mode' => 'embedded',
		'line_items' => $products,
		'mode' => 'payment',
		'return_url' => $YOUR_DOMAIN . '/return.html?session_id={CHECKOUT_SESSION_ID}',
	]);

	return new WP_REST_Response(['clientSecret' => $checkout_session->client_secret]);
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