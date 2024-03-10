<?php
/*
Plugin Name: Mcms Plugin
Plugin URI: http://URI_Of_Page_Describing_Plugin_and_Updates
Description: Mcms Plugin to handle the integration with Astro
Version: 1.3.3
Author: mbouclas
Author URI: http://URI_Of_The_Plugin_Author
License: A "Slug" license name e.g. GPL2
*/
require __DIR__ . '/vendor/autoload.php';


require_once 'handlers/rest-route-handlers.php';
require_once 'handlers/api-rest-route-handlers.php';
require_once 'handlers/woo-rest-route-handlers.php';
require_once 'handlers/install-handlers.php';
require_once 'handlers/mcms-options-page-handlers.php';
require_once 'handlers/cloudflare-rest-route-handlers.php';
require_once 'handlers/sync-route-handlers.php';
require_once 'handlers/overrides.php';
require_once 'includes/rest-routes.php';
require_once 'includes/woocomrce-routes.php';
require_once 'includes/settings-page.php';
require_once 'middleware/auth.php';

// register the plugin
register_activation_hook( __FILE__, 'mcms_plugin_activate' );
register_deactivation_hook( __FILE__, 'mcms_plugin_deactivate' );

add_action( 'admin_menu', 'Mcms\Handlers\OptionsPage\mcms_options_page' );
add_action( 'admin_enqueue_scripts', 'enque_mcms_scripts' );
add_action('admin_enqueue_scripts', 'enqueue_admin_styles');
add_action('admin_footer', 'add_custom_html_to_admin_footer', 999);
//add_action('admin_bar_menu', 'add_toolbar_button', 999);
add_action('add_meta_boxes', 'add_mcms_controls');
add_filter('script_loader_tag', 'add_type_attribute' , 10, 3);
add_filter('wp_insert_post_data', 'Mcms\Api\RestRouteHandlers\Overrides\sanitize_post_slug', 10, 2);
add_filter('wp_update_term_data', 'Mcms\Api\RestRouteHandlers\Overrides\sanitize_term_slug', 10, 3);
add_filter('rest_prepare_attachment', 'Mcms\Api\RestRouteHandlers\Overrides\add_cloudinary_url_to_media_query', 10, 3);
add_filter('rest_pre_dispatch', 'Mcms\Middleware\Auth\api_key_check', 10, 4);
$post_types = get_post_types();


if (in_array('sitepress-multilingual-cms/sitepress.php', get_option('active_plugins'))) {
	foreach ($post_types as $post_type) {
		add_filter('rest_prepare_' . $post_type, 'Mcms\Api\RestRouteHandlers\Overrides\add_related_languages_to_item', 10, 3);
	}
}

// By default, the product_variation post type is not public. This filter makes it public.
function make_product_variations_post_type_public($args, $post_type) {
	if ('product_variation' === $post_type) {
		$args['public'] = true;
	}
	return $args;
}
add_filter('register_post_type_args', 'make_product_variations_post_type_public', 10, 2);

// Render acf inside the variations tab. Javascript fields are not working.
add_action( 'woocommerce_product_after_variable_attributes', function( $loop, $variation_data, $variation ) {
	global $abcdefgh_i; // Custom global variable to monitor index
	$abcdefgh_i = $loop;

	// Add filter to update field name
	add_filter( 'acf/prepare_field', 'acf_prepare_field_update_field_name' );

	// Loop through all field groups
	$acf_field_groups = acf_get_field_groups();

	foreach( $acf_field_groups as $acf_field_group ) {
		foreach( $acf_field_group['location'] as $group_locations ) {
			foreach( $group_locations as $rule ) {

				// See if field Group has at least one post_type = Variations rule - does not validate other rules
//                print_r($rule);
				if( $rule['param'] == 'post_type' && $rule['operator'] == '==' && $rule['value'] == 'product_variation' ) {
					// Render field Group

					acf_render_fields( $variation->ID, acf_get_fields( $acf_field_group ) );
					break 2;
				}
			}
		}
	}

	// Remove filter
	remove_filter( 'acf/prepare_field', 'acf_prepare_field_update_field_name' );
}, 10, 3 );

function  acf_prepare_field_update_field_name( $field ) {
	global $abcdefgh_i;
	$field['name'] = preg_replace( '/^acf\[/', "acf[$abcdefgh_i][", $field['name'] );
	return $field;
}

// Save variation data
add_action( 'woocommerce_save_product_variation', function( $variation_id, $i = -1 ) {
	// Update all fields for the current variation
	if ( ! empty( $_POST['acf'] ) && is_array( $_POST['acf'] ) && array_key_exists( $i, $_POST['acf'] ) && is_array( ( $fields = $_POST['acf'][ $i ] ) ) ) {
		foreach ( $fields as $key => $val ) {
			update_field( $key, $val, $variation_id );
		}
	}
}, 10, 2 );

function mcms_plugin_activate() {

}

function mcms_plugin_deactivate() {

}

function add_mcms_controls() {
	add_meta_box(
		'mcms_controls', // Unique ID
		'Mcms', // Box title
		'mcms_meta_box', // Content callback, must be of type callable
		null, // Post type
        'side', // Position
	);
}

function mcms_meta_box($post) {
	echo '<refresh-data mode="full"></refresh-data>';
}

function enque_mcms_scripts( $hook ) {


	wp_enqueue_script(
		'mcsm-app',
		plugins_url( '/assets/main.js', __FILE__ ),
		[],
		'1.1.12',
		[
			'in_footer' => true,
			'type' => 'module'
		]
	);

}

function enqueue_admin_styles() {
//	wp_enqueue_style( 'mcms-styles', plugins_url( '/assets/styles.css', __FILE__ ), false, '1.1.11' );
}

function add_type_attribute($tag, $handle, $src) {

	// if not your script, do nothing and return original $tag
	if ( 'mcsm-app' !== $handle ) {
		return $tag;
	}
	// change the script tag by adding type="module" and return it.
	$tag = '<script type="module" src="' . esc_url( $src ) . '"></script>';
	return $tag;
}

function add_custom_html_to_admin_footer() {
	?>
	<div class="p-4">
	<mcms-app  baseUrl=""></mcms-app>
	<notifications-bar></notifications-bar>
	</div>
<?php
}

function add_toolbar_button($wp_admin_bar) {
	$args = array(
		'id' => 'refresh_data_button', // The ID of your button
		'title' => '<refresh-data></refresh-data>', // The title of your button with custom HTML
		'meta' => array(

		)
	);
	$wp_admin_bar->add_node($args);
}





