<?php
namespace Mcms\Handlers\Subscribers;

use \WP_REST_Request;
use \WP_REST_Response;

/**
 * Add a new subscriber with email only
 *
 * @param WP_REST_Request $request
 *
 * @return WP_REST_Response
 */
function store(WP_REST_Request $request) {
	$email = $request->get_param('email');
	if (empty($email)) {
		return new WP_REST_Response([
			'error' => 'email_required',
		], 400);
	}

	if (email_exists($email)) {
		return new WP_REST_Response([
			'error' => 'email_exists',
		], 400);
	}

	$user_id = wp_insert_user([
		'user_login' => $email,
		'user_email' => $email,
		'user_pass' => null, // When creating a user, `user_pass` is expected.
		'role' => 'subscriber'
	]);

	if (is_wp_error($user_id)) {
		return new WP_REST_Response([
			'error' => $user_id->get_error_message(),
		], 400);
	}

	return new WP_REST_Response([
		'success' => true,
	]);
}

function update(WP_REST_Request $request) {
	$email = $request->get_param('email');
	if (empty($email)) {
		return new WP_REST_Response([
			'error' => 'email_required',
		], 400);
	}

	$user = get_user_by('email', $email);
	if (!$user) {
		return new WP_REST_Response([
			'error' => 'user_not_found',
		], 400);
	}

	$user_id = $user->ID;

	$updated = wp_update_user([
		'ID' => $user_id,
		'user_email' => $email,
		'first_name' => $request->get_param('first_name'),
		'last_name' => $request->get_param('last_name'),
		'phone' => $request->get_param('phone'),
	]);

	if (is_wp_error($updated)) {
		return new WP_REST_Response([
			'error' => $updated->get_error_message(),
		], 400);
	}

	return new WP_REST_Response([
		'success' => true,
	]);
}