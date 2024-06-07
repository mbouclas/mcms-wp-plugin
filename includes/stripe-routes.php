<?php
add_action( 'rest_api_init', function () {
	register_rest_route( 'mcms/v1', '/store/checkout/session/', [
		'methods'  => 'GET',
		'callback' => '\Mcms\Api\StripeRestRouteHandlers\create_checkout_session',
		'permission_callback' => '\Mcms\Middleware\Auth\addUserSession',
	] );

	register_rest_route( 'mcms/v1', '/store/stripe/webhook/', [
		'methods'  => 'POST',
		'callback' => '\Mcms\Api\StripeRestRouteHandlers\webhook',
		'permission_callback' => '\Mcms\Middleware\Auth\addUserSession',
	] );

	register_rest_route( 'mcms/v1', '/store/checkout/validate-status/', [
		'methods'  => 'POST',
		'callback' => '\Mcms\Api\StripeRestRouteHandlers\processStatus',
		'permission_callback' => '\Mcms\Middleware\Auth\addUserSession',
	] );

});