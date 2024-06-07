<?php
add_action( 'rest_api_init', function () {
	register_rest_route( 'mcms/v1', '/media/', [
		'methods'  => 'GET',
		'callback' => '\Mcms\Api\WooRestRouteHandlers\get_media',
		'permission_callback' => '__return_true',
	] );

	register_rest_route( 'mcms/v1', '/store/products/', [
		'methods'  => 'GET',
		'callback' => '\Mcms\Api\WooRestRouteHandlers\get_products',
		'permission_callback' => '__return_true',
	] );

	register_rest_route( 'mcms/v1', '/store/product-attributes/', [
		'methods'  => 'GET',
		'callback' => '\Mcms\Api\WooRestRouteHandlers\get_product_attributes',
		'permission_callback' => '__return_true',
	] );


	register_rest_route( 'wp/v2', '/store/product_variations/', [
		'methods'  => 'GET',
		'callback' => '\Mcms\Api\WooRestRouteHandlers\product_variations',
		'permission_callback' => '__return_true',
	] );


	register_rest_route( 'mcms/v1', '/store/attributes/', [
		'methods'  => 'GET',
		'callback' => '\Mcms\Api\WooRestRouteHandlers\get_attributes',
		'permission_callback' => '__return_true',
	] );


	register_rest_route( 'mcms/v1', '/store/cart/', [
		'methods'  => 'GET',
		'callback' => '\Mcms\Api\WooRestRouteHandlers\get_cart',
		'permission_callback' => '\Mcms\Middleware\Auth\addUserSession',
	] );

	register_rest_route( 'mcms/v1', '/store/cart/add', [
		'methods'  => 'POST',
		'callback' => '\Mcms\Api\WooRestRouteHandlers\add_to_cart',
		'permission_callback' => '\Mcms\Middleware\Auth\addUserSession',
	] );

	register_rest_route( 'mcms/v1', '/store/cart/clear', [
		'methods'  => 'DELETE',
		'callback' => '\Mcms\Api\WooRestRouteHandlers\clear_cart',
		'permission_callback' => '\Mcms\Middleware\Auth\addUserSession',
	] );

	register_rest_route( 'mcms/v1', '/store/shipping/methods', [
		'methods'  => 'GET',
		'callback' => '\Mcms\Api\WooRestRouteHandlers\getShippingMethods',
		'permission_callback' => '\Mcms\Middleware\Auth\addUserSession',
	] );

	register_rest_route( 'mcms/v1', '/store/payment/methods', [
		'methods'  => 'GET',
		'callback' => '\Mcms\Api\WooRestRouteHandlers\getAvailablePaymentMethods',
		'permission_callback' => '\Mcms\Middleware\Auth\addUserSession',
	] );

	register_rest_route( 'mcms/v1', '/store/payment/settings', [
		'methods'  => 'POST',
		'callback' => '\Mcms\Api\WooRestRouteHandlers\getPaymentSettings',
		'permission_callback' => '\Mcms\Middleware\Auth\addUserSession',
	] );

	register_rest_route( 'mcms/v1', '/store/shipping/method', [
		'methods'  => 'POST',
		'callback' => '\Mcms\Api\WooRestRouteHandlers\setShippingMethod',
		'permission_callback' => '\Mcms\Middleware\Auth\addUserSession',
	] );

	register_rest_route( 'mcms/v1', '/store/checkout', [
		'methods'  => 'POST',
		'callback' => '\Mcms\Api\WooRestRouteHandlers\checkout',
		'permission_callback' => '__return_true',
	] );

	register_rest_route( 'mcms/v1', '/store/checkout-fields', [
		'methods'  => 'GET',
		'callback' => '\Mcms\Api\WooRestRouteHandlers\getCheckoutFields',
		'permission_callback' => '__return_true',
	] );

	register_rest_route( 'mcms/v1', '/store/customer', [
		'methods'  => 'GET',
		'callback' => '\Mcms\Api\WooRestRouteHandlers\get_customer',
		'permission_callback' => '\Mcms\Middleware\Auth\addUserSession',
	] );

	register_rest_route( 'mcms/v1', '/store/init', [
		'methods'  => 'GET',
		'callback' => '\Mcms\Api\WooRestRouteHandlers\frontEndInit',
		'permission_callback' => '\Mcms\Middleware\Auth\addUserSession',
	] );

	register_rest_route( 'mcms/v1', '/store/login', [
		'methods'  => 'POST',
		'callback' => '\Mcms\Api\WooRestRouteHandlers\login_user',
		'permission_callback' => '__return_true',
	] );

	register_rest_route( 'mcms/v1', '/store/settings', [
		'methods'  => 'GET',
		'callback' => '\Mcms\Api\WooRestRouteHandlers\getStoreSettings',
		'permission_callback' => '__return_true',
	] );

	register_rest_route( 'mcms/v1', '/store/customer/check-existing-email', [
		'methods'  => 'POST',
		'callback' => '\Mcms\Api\CustomerRestRouteHandlers\checkExistingEmail',
		'permission_callback' => '__return_true',
	] );

	register_rest_route( 'mcms/v1', '/store/customer/register', [
		'methods'  => 'POST',
		'callback' => '\Mcms\Api\CustomerRestRouteHandlers\registerCustomer',
		'permission_callback' => '__return_true',
	] );
	

	register_rest_route( 'mcms/v1', '/store/customer/address', [
		'methods'  => 'PATCH',
		'callback' => '\Mcms\Api\CustomerRestRouteHandlers\saveCustomerAddress',
		'permission_callback' => '__return_true',
	] );

	register_rest_route( 'mcms/v1', '/store/customer/reset-password/', [
		'methods' => 'POST',
		'callback' => '\Mcms\Api\CustomerRestRouteHandlers\send_reset_password_email',
		'permission_callback' => '__return_true',
	]);

	register_rest_route( 'mcms/v1', '/store/customer/change-password/', [
		'methods' => 'POST',
		'callback' => '\Mcms\Api\CustomerRestRouteHandlers\reset_password',
		'permission_callback' => '__return_true',
	]);

	register_rest_route( 'mcms/v1', '/store/customer/validate-reset-key/', [
		'methods' => 'GET',
		'callback' => '\Mcms\Api\CustomerRestRouteHandlers\validate_reset_key',
		'permission_callback' => '__return_true',
	]);

	register_rest_route( 'mcms/v1', '/my-account/lost-password', [
		'methods' => 'GET',
		'callback' => '\Mcms\Api\CustomerRestRouteHandlers\validate_reset_password_token',
		'permission_callback' => '__return_true',
	]);


	register_rest_route( 'mcms/v1', '/store/customer/orders', [
		'methods'  => 'GET',
		'callback' => '\Mcms\Api\CustomerRestRouteHandlers\getOrders',
		'permission_callback' => '\Mcms\Middleware\Auth\addUserSession',
	] );

	register_rest_route( 'mcms/v1', '/store/customer/order', [
		'methods'  => 'GET',
		'callback' => '\Mcms\Api\CustomerRestRouteHandlers\getOrder',
		'permission_callback' => '\Mcms\Middleware\Auth\addUserSession',
	] );

	register_rest_route( 'mcms/v1', '/store/customer/addresses', [
		'methods'  => 'GET',
		'callback' => '\Mcms\Api\CustomerRestRouteHandlers\getAddresses',
		'permission_callback' => '\Mcms\Middleware\Auth\addUserSession',
	] );

	register_rest_route( 'mcms/v1', '/store/customer/password-reset', [
		'methods'  => 'POST',
		'callback' => '\Mcms\Api\CustomerRestRouteHandlers\changePassword',
		'permission_callback' => '\Mcms\Middleware\Auth\addUserSession',
	] );
} );