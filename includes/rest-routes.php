<?php
namespace Mcms\Includes;
use function Cloudinary\get_plugin_instance;

add_action( 'rest_api_init', function () {


	register_rest_route( 'mcms/v1', '/fields/', [
		'methods' => 'GET',
		'callback' => '\Mcms\Handlers\handle_get_acf_group_fields_request',
		'permission_callback' => '__return_true',
	]);
	
	register_rest_route( 'mcms/v1', '/options-pages/', [
	    'methods' => 'GET',
	    'callback' => '\Mcms\Handlers\handle_get_acf_options_pages_request',
		'permission_callback' => '__return_true',
	]);



	register_rest_route( 'mcms/v1', '/menus/', [
		'methods' => 'GET',
		'callback' => '\Mcms\Handlers\handle_get_menus_request',
		'permission_callback' => '__return_true',
	]);

	register_rest_route( 'mcms/v1', '/install/', [
		'methods' => 'GET',
		'callback' => '\Mcms\Install\handle_install_car_data_request',
		'permission_callback' => '__return_true',
	]);

	register_rest_route( 'mcms/v1', '/contact/', array(
		'methods' => 'POST',
		'callback' => '\Mcms\Handlers\handle_contact_request',
		'permission_callback' => '__return_true',
	) );

	register_rest_route( 'mcms/v1', '/test/', [
		'methods' => 'GET',
		'callback' => 'Mcms\Includes\test',
		'permission_callback' => '__return_true',
	]);

	register_rest_route( 'mcms/v1', '/api/boot/', [
		'methods' => 'GET',
		'callback' => '\Mcms\Api\RestRouteHandlers\handle_boot_request',
		'permission_callback' => '__return_true',
	]);

	register_rest_route( 'mcms/v1', '/api/sync/refresh/', [
		'methods' => 'GET',
		'callback' => '\Mcms\Api\RestRouteHandlers\Sync\handle_refreshData_request',
		'permission_callback' => '__return_true',
	]);

	register_rest_route( 'mcms/v1', '/api/sync/history/', [
		'methods' => 'GET',
		'callback' => '\Mcms\Api\RestRouteHandlers\CloudFlare\handle_getHistory_request',
		'permission_callback' => '__return_true',
	]);

	register_rest_route( 'mcms/v1', '/api/sync/status/', [
		'methods' => 'GET',
		'callback' => '\Mcms\Api\RestRouteHandlers\CloudFlare\handle_getBuildStatus_request',
		'permission_callback' => '__return_true',
	]);

	register_rest_route( 'mcms/v1', '/api/sync/build/', [
		'methods' => 'POST',
		'callback' => '\Mcms\Api\RestRouteHandlers\CloudFlare\handle_build_request',
		'permission_callback' => '__return_true',
	]);

	register_rest_route( 'mcms/v1', '/post-types/', [
		'methods' => 'GET',
		'callback' => '\Mcms\Handlers\handle_get_all_post_types',
		'permission_callback' => '__return_true',
	]);

	register_rest_route( 'mcms/v1', '/subscriber/', [
		'methods' => 'POST',
		'callback' => '\Mcms\Handlers\Subscribers\store',
		'permission_callback' => '__return_true',
	]);

	register_rest_route( 'mcms/v1', '/subscriber/', [
		'methods' => 'PATCH',
		'callback' => '\Mcms\Handlers\Subscribers\update',
		'permission_callback' => '__return_true',
	]);



	register_rest_route( 'mcms/v1', '/cloudinary-test/', [
		'methods' => 'GET',
		'callback' => function() {
			$plugin = get_plugin_instance();
			$base_query_args = array(
				'post_type'              => 'attachment',
				'post_status'            => 'inherit',
				'fields'                 => 'ids',
				'posts_per_page'         => 100,
				'update_post_term_cache' => false,
				'update_post_meta_cache' => false,
				'paged'                  => 1,
			);
			$query_args = $base_query_args;
			// phpcs:ignore WordPress.DB.SlowDBQuery
			$query_args['meta_query'] = array(
				'AND',
				array(
					'key'     => '_cld_unsynced',
					'compare' => 'EXISTS',
				),
			);
			$query = new \WP_Query( $query_args );
			$sync = $plugin->get_component('sync');
			foreach ( $query->get_posts() as $index => $asset ) {
				$file     = get_attached_file( $asset );
				if (!$sync->is_synced($asset, true)) {
					$p = $sync->managers['push']->process_assets( $asset );
					print_r($p);
				}


			}



//			$attachment_url = $cloudinary->upload_asset(357);
//			print_r($attachment_url);
/*			if (is_wp_error($attachment_url)) {
				return new \WP_REST_Response( $attachment_url->get_error_message(), 500 );
			}*/
/*			$cloudinary->uploadApi()->upload('https://res.cloudinary.com/demo/image/upload/sample.jpg');
			return new \WP_REST_Response( 'success' );*/
		},
		'permission_callback' => '__return_true',
	]);
});

add_action('add_attachment', function ($attachment_id) {
	error_log('**** '.$attachment_id);
	// Your custom code here.
});


function test() {
	$output = shell_exec('./test.sh');

	return new \WP_REST_Response( $output );
}