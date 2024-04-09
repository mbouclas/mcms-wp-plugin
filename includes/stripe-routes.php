<?php
add_action( 'rest_api_init', function () {
	register_rest_route( 'mcms/v1', '/store/checkout/session/', [
		'methods'  => 'GET',
		'callback' => '\Mcms\Api\StripeRestRouteHandlers\create_checkout_session',
		'permission_callback' => '\Mcms\Middleware\Auth\addUserSession',
	] );
});