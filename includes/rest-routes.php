<?php
namespace Mcms\Includes;
add_action( 'rest_api_init', function () {


	register_rest_route( 'mcms/v1', '/fields/', [
		'methods' => 'GET',
		'callback' => '\Mcms\Handlers\handle_get_acf_group_fields_request',
	]);
	
	register_rest_route( 'mcms/v1', '/options-pages/', [
	    'methods' => 'GET',
	    'callback' => '\Mcms\Handlers\handle_get_acf_options_pages_request',
	]);

	register_rest_route( 'mcms/v1', '/menus/', [
		'methods' => 'GET',
		'callback' => '\Mcms\Handlers\handle_get_menus_request',
	]);

	register_rest_route( 'mcms/v1', '/install/', [
		'methods' => 'GET',
		'callback' => '\Mcms\Install\handle_install_car_data_request',
	]);

	register_rest_route( 'mcms/v1', '/contact/', array(
		'methods' => 'POST',
		'callback' => '\Mcms\Handlers\handle_contact_request',
	) );

	register_rest_route( 'mcms/v1', '/test/', [
		'methods' => 'GET',
		'callback' => 'Mcms\Includes\test',
	]);

	register_rest_route( 'mcms/v1', '/api/boot/', [
		'methods' => 'GET',
		'callback' => '\Mcms\Api\RestRouteHandlers\handle_boot_request',
	]);

	register_rest_route( 'mcms/v1', '/api/sync/refresh/', [
		'methods' => 'GET',
		'callback' => '\Mcms\Api\RestRouteHandlers\Sync\handle_refreshData_request',
	]);

	register_rest_route( 'mcms/v1', '/api/sync/history/', [
		'methods' => 'GET',
		'callback' => '\Mcms\Api\RestRouteHandlers\CloudFlare\handle_getHistory_request',
	]);

	register_rest_route( 'mcms/v1', '/api/sync/status/', [
		'methods' => 'GET',
		'callback' => '\Mcms\Api\RestRouteHandlers\CloudFlare\handle_getBuildStatus_request',
	]);

	register_rest_route( 'mcms/v1', '/api/sync/build/', [
		'methods' => 'POST',
		'callback' => '\Mcms\Api\RestRouteHandlers\CloudFlare\handle_build_request',
	]);
});


function test() {
	$output = shell_exec('./test.sh');

	return new \WP_REST_Response( $output );
}