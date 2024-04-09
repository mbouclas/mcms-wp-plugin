<?php
namespace Mcms\Api\CustomerRestRouteHandlers;
use WP_REST_Request;
use WP_REST_Response;

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
	$firstName = $request->get_param('firstName');
	$lastName = $request->get_param('lastName');

	if (empty($username)) {
		$username = $email;
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