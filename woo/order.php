<?php
namespace Mcms\Woo\Order;

class Order {
	function __construct() {

	}

	/**
	 * @param $opts
	 * 'notes' => 'string',
	 *
	 * @return int
	 */
	public function create($opts = []) {

		$order = wc_create_order(array(
			'status'        => 'pending',
			'customer_id'   => WC()->customer->get_id(), // or the customer id
			'customer_note' =>  $opts['customer_note'] ?? '',
		));

		// Get cart items
		$cart_items = WC()->cart->get_cart();

		// Loop through each cart item and add it to the order
		foreach ($cart_items as $cart_item_key => $cart_item) {
			$product = $cart_item['data'];
			$quantity = $cart_item['quantity'];
			$order->add_product($product, $quantity);
		}

		// Calculate totals
		$order->calculate_totals();
		$order->save();

		return $order->get_id();
	}
}