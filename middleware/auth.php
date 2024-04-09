<?php
namespace Mcms\Middleware\Auth;
use \WP_Error;
use \WP_REST_Request;
use \WP_REST_Server;

function api_key_check( $result, WP_REST_Server $server, WP_REST_Request $request) {
	$is_rest_secure = get_field('secure_rest', 'option');
	$referer_host = isset($_SERVER['HTTP_REFERER']) ? parse_url($_SERVER['HTTP_REFERER'], PHP_URL_HOST) : null;

	// exempted routes
	$exempted_routes = [
		'/wp-json/mcms/v1/store/*',
	];

	$exempted = false;
	foreach ($exempted_routes as $route) {
		// if the route has a trailing * then use fnmatch
		if (fnmatch($route, $_SERVER['REQUEST_URI'])) {
			$exempted = true;
			break;
		}
	}

	if ($exempted) {
		return null;
	}

	if (!$is_rest_secure || $referer_host == $_SERVER['SERVER_NAME']) {
		return null;
	}





	$api_key = $request->get_header('X-API-KEY');
	$key = get_field('rest_api_key', 'option');


	if (empty($api_key) || $api_key !== $key) {
		return new WP_Error('rest_forbidden', 'Invalid API Key', array('status' => 403));
	}

	// If the API key is valid, return null so the request can proceed
	return null;
}

function addUserSession( WP_REST_Request $request) {
	static $counter = 0;
	$counter++;
	$header = substr($request->get_header('Authorization'), 7);
	// for any reason, if the header is empty, return true
	if (empty($header)) {
		return true;
	}

	$currentUserId = \Mcms\Includes\Auth\validate_jwt($header);

	// if the user id is not valid, return null
	if (!$currentUserId || is_array($currentUserId)) {
		return true;
	}

	if (null === WC()->session) {
		WC()->session = new \WC_Session_Handler();
		WC()->session->init();
	}

	if (null === WC()->cart) {
		WC()->initialize_cart();
	}


	wp_set_current_user( $currentUserId );
	wp_set_current_user($currentUserId);
	wp_set_auth_cookie($currentUserId);
	WC()->session->set('user_id', $currentUserId);
	return true;
}

function is_localhost() {
	$whitelist = array('127.0.0.1', '::1', 'localhost');
	return in_array($_SERVER['REMOTE_ADDR'], $whitelist);
}