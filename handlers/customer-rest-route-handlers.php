<?php
namespace Mcms\Api\CustomerRestRouteHandlers;
use WP_REST_Request;
use WP_REST_Response;
use WP_User_Query;

function checkExistingEmail(WP_REST_Request $request) {
	$email = $request->get_param('email');
	$user = get_user_by('email', $email);
	return new WP_REST_Response([
		'exists' => (bool) $user
	]);
}

function registerCustomer(WP_REST_Request $request) {
	$email = $request->get_param('email');
	$username = $request->get_param('username');
	$password = $request->get_param('password');
	$firstName = $request->get_param('firstName') ?: $request->get_param('first_name');
	$lastName = $request->get_param('lastName') ?: $request->get_param('last_name');

	if (empty($username)) {
		$username = $email;
	}

	if (empty($password)) {
		$password = wp_generate_password(12, false);
		$request->set_param('password', $password);
	}

	$user_id = wc_create_new_customer($email, $username, $password);

	if (is_wp_error($user_id)) {
		return new WP_REST_Response([
			'message' => 'failed_to_create_customer',
			'error' => $user_id->get_error_message(),
		], 500);
	}



	wp_update_user([
		'ID' => $user_id,
		'first_name' => $firstName,
		'last_name' => $lastName,
	]);

	$request->set_param('username', $username);
	$login = \Mcms\Api\WooRestRouteHandlers\login_user($request);

	// this is tricky. IF it's a guest, only send after place order and IF they have checked to create an account
//	wp_new_user_notification($user_id, null, 'user');

	return new WP_REST_Response([
		'message' => 'customer_created',
		'user' => $login->data['user'],
		'token' => $login->data['token'],
	]);
}

function saveCustomerAddress(WP_REST_Request $request) {
	$customerId = null;
	$type = $request->get_param('type') ?: 'billing';
	try {
		$customerId = \Mcms\Includes\Auth\validate_jwt(substr($request->get_header('Authorization'), 7));
	}
	catch (\Exception $e) {
		return new WP_REST_Response([
			'message' => 'invalid_token',
			'error' => $e->getMessage(),
		], 401);
	}

	$address = [];
	$address['first_name'] = $request->get_param('first_name');
	$address['last_name'] = $request->get_param('last_name');
	$address['email'] = $request->get_param('email');
	$address['phone'] = $request->get_param('phone');
	$address['company'] = $request->get_param('company');
	$address['address_1'] = $request->get_param('address_1');
	$address['address_2'] = $request->get_param('address_2');
	$address['city'] = $request->get_param('city');
	$address['postcode'] = $request->get_param('postcode');
	$address['country'] = $request->get_param('country');
	$address['state'] = $request->get_param('state');

	foreach ( $address as $key => $value ) {
		update_user_meta( $customerId, $type.'_' . $key, $value );
	}


	return new WP_REST_Response([
		'message' => 'address_updated',
	]);

}

function send_reset_password_email(WP_REST_Request $request) {
	$email = $request->get_param('email');
	$user = get_user_by('email', $email);


	$reset_key = get_password_reset_key($user);

	if (is_wp_error($reset_key)) {
		return new \WP_Error('reset_password_key_error', $reset_key->get_error_message(), array('status' => 400));
	}

	update_user_meta($user->ID, 'password_reset_key', $reset_key);
	update_user_meta($user->ID, 'password_reset_key_time', time()); // Optionally save the time the key was generated

	do_action('retrieve_password_key', $user->user_login, $reset_key);

	// WooCommerce will handle the email sending
	WC()->mailer()->emails['WC_Email_Customer_Reset_Password']->trigger($user->user_login, $reset_key);

	return new WP_REST_Response([
		'success' => true,
	]);

}

function validate_reset_password_token(WP_REST_Request $request) {
	$reset_token = $request->get_param('key');;

	 if (is_wp_error(checkResetToken($reset_token))) {
		return new \WP_Error('invalid_token', 'Invalid reset token', array('status' => 400));
	 }

	return new WP_REST_Response([
		'success' => true,
	]);
}

function checkResetToken($reset_token) {
	$user_query = new WP_User_Query(array(
		'meta_key' => 'password_reset_key',
		'meta_value' => $reset_token,
	));

	if (!$user_query->get_total()) {
		return new \WP_Error('invalid_token', 'Invalid reset token', array('status' => 400));
	}


	$keyIsValid = check_password_reset_key(get_user_meta($user_query->get_results()[0]->ID, 'password_reset_key', true), $user_query->get_results()[0]->user_login);
	if (is_wp_error($keyIsValid)) {
		return new \WP_Error('invalid_token', 'Invalid reset token', array('status' => 400));
	}

	return $user_query->get_results()[0];
}

function reset_password(WP_REST_Request $request) {
	$reset_token = $request->get_param('key');;

	$user = checkResetToken($reset_token);

	if (is_wp_error($user)) {
		return new \WP_Error('invalid_token', 'Invalid reset token', array('status' => 400));
	}


	$password = $request->get_param('password');
	wp_set_password($password, $user->ID);

	delete_user_meta($user->ID, 'password_reset_key', null);
	delete_user_meta($user->ID, 'password_reset_key_time', null);

	return new WP_REST_Response([
		'success' => true,
	]);
}

function validate_reset_key(WP_REST_Request $request) {
	$reset_token = $request->get_param('key');;

	$user = checkResetToken($reset_token);

	if (is_wp_error($user)) {
		return new \WP_Error('invalid_token', 'Invalid reset token', array('status' => 400));
	}

	return new WP_REST_Response([
		'success' => true,
		'email' => $user->user_email,
	]);
}


function getOrders(WP_REST_Request $request) {
	$user_id = get_current_user_id(); // Get the current user ID
	$query = new \WC_Order_Query( array(
		'customer_id' => $user_id,
		'status' => 'completed',
	) );
	$orders = $query->get_orders();
	$data = [];
	foreach ($orders as $order) {
		$data[] = [
			'id' => $order->get_id(),
			'number' => $order->get_order_number(),
			'total' => $order->get_total(),
			'status' => $order->get_status(),
			'currency' => $order->get_currency(),
			'date' => $order->get_date_created()->date('Y-m-d H:i:s'),
		];
	}

	return new \WP_REST_Response($data);
}

function getOrder(WP_REST_Request $request) {
	$order_id = $request->get_param('id');
	$order = wc_get_order($order_id);
	$data = [
		'id' => $order->get_id(),
		'number' => $order->get_order_number(),
		'total' => $order->get_total(),
		'subtotal' => $order->get_subtotal(),
		'shipping_total' => $order->get_shipping_total(),
		'tax_total' => $order->get_total_tax(),
		'payment_method' => $order->get_payment_method(),
		'status' => $order->get_status(),
		'currency' => $order->get_currency(),
		'date' => $order->get_date_created()->date('Y-m-d H:i:s'),
		'notes' => $order->get_customer_note(),
		'line_items' => [],
	];

	foreach ($order->get_items() as $item_id => $item) {
		$product = $item->get_product();
		$line_item = [
			'id' => $item_id,
			'product_id' => $product->get_id(),
			'name' => $product->get_name(),
			'attributes' => $item->get_meta_data(),
			'image' => wp_get_attachment_image_src($product->get_image_id(), 'full')[0],
			'quantity' => $item->get_quantity(),
			'price' => $item->get_total(),
		];

		if ($product->is_type('variation')) {
//			$line_item['variation_id'] = $product->get_id();
			$line_item['name'] = implode(" ", $product->get_variation_attributes());
		}

		$data['line_items'][] = $line_item;
	}

	return new \WP_REST_Response($data);
}

function getAddresses(WP_REST_Request $request) {
	$user_id = get_current_user_id(); // Get the current user ID
	$billing_address = [];
	$shipping_address = [];
	$billing_address['firstName'] = get_user_meta($user_id, 'billing_first_name', true);
	$billing_address['lastName'] = get_user_meta($user_id, 'billing_last_name', true);
	$billing_address['email'] = get_user_meta($user_id, 'billing_email', true);
	$billing_address['phone'] = get_user_meta($user_id, 'billing_phone', true);
	$billing_address['company'] = get_user_meta($user_id, 'billing_company', true);
	$billing_address['address_1'] = get_user_meta($user_id, 'billing_address_1', true);
	$billing_address['address_2'] = get_user_meta($user_id, 'billing_address_2', true);
	$billing_address['city'] = get_user_meta($user_id, 'billing_city', true);
	$billing_address['postcode'] = get_user_meta($user_id, 'billing_postcode', true);
	$billing_address['country'] = get_user_meta($user_id, 'billing_country', true);
	$billing_address['state'] = get_user_meta($user_id, 'billing_state', true);

	$shipping_address['firstName'] = get_user_meta($user_id, 'shipping_first_name', true);
	$shipping_address['lastName'] = get_user_meta($user_id, 'shipping_last_name', true);
	$shipping_address['email'] = get_user_meta($user_id, 'shipping_email', true);
	$shipping_address['phone'] = get_user_meta($user_id, 'shipping_phone', true);
	$shipping_address['company'] = get_user_meta($user_id, 'shipping_company', true);
	$shipping_address['address_1'] = get_user_meta($user_id, 'shipping_address_1', true);
	$shipping_address['address_2'] = get_user_meta($user_id, 'shipping_address_2', true);
	$shipping_address['city'] = get_user_meta($user_id, 'shipping_city', true);
	$shipping_address['postcode'] = get_user_meta($user_id, 'shipping_postcode', true);
	$shipping_address['country'] = get_user_meta($user_id, 'shipping_country', true);
	$shipping_address['state'] = get_user_meta($user_id, 'shipping_state', true);

	return new \WP_REST_Response([
		'firstName' => get_user_meta($user_id, 'first_name', true),
		'lastName' => get_user_meta($user_id, 'last_name', true),
		'billing' => $billing_address,
		'shipping' => $shipping_address,
	]);
}

function changePassword(WP_REST_Request $request) {
	$user_id = get_current_user_id();
	$password = $request->get_param('password');
	wp_set_password($password, $user_id);

	return new WP_REST_Response([
		'message' => 'password_changed',
	]);
}