<?php
namespace Mcms\Middleware\Auth;
use \WP_Error;
use \WP_REST_Request;
use \WP_REST_Server;

function api_key_check( $result, WP_REST_Server $server, WP_REST_Request $request) {
	$is_rest_secure = get_field('secure_rest', 'option');
	if (!$is_rest_secure) {
		return null;
	}

	$api_key = $request->get_header('X-API-KEY');
	$key = get_field('rest_api_key', 'option');

	// Replace 'your-api-key' with your actual API key
	if (empty($api_key) || $api_key !== $key) {
		return new WP_Error('rest_forbidden', 'Invalid API Key', array('status' => 403));
	}

	// If the API key is valid, return null so the request can proceed
	return null;
}

function is_localhost() {
	$whitelist = array('127.0.0.1', '::1', 'localhost');
	return in_array($_SERVER['REMOTE_ADDR'], $whitelist);
}