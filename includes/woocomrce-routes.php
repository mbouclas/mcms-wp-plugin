<?php
add_action( 'rest_api_init', function () {


	register_rest_route( 'mcms/v1', '/store/products/', [
		'methods'  => 'GET',
		'callback' => '\Mcms\Api\RestRouteHandlers\get_products',
		'permission_callback' => '__return_true',
	] );

	register_rest_route( 'mcms/v1', '/store/attributes/', [
		'methods'  => 'GET',
		'callback' => '\Mcms\Api\RestRouteHandlers\get_attributes',
		'permission_callback' => '__return_true',
	] );

} );