<?php
/*
Plugin Name: Mcms Plugin
Plugin URI: http://URI_Of_Page_Describing_Plugin_and_Updates
Description: Mcms Plugin to handle the integration with Astro
Version: 1.4.3
Author: mbouclas
Author URI: http://URI_Of_The_Plugin_Author
License: A "Slug" license name e.g. GPL2
*/
require __DIR__ . '/vendor/autoload.php';


require_once 'handlers/rest-route-handlers.php';
require_once 'handlers/api-rest-route-handlers.php';
require_once 'handlers/subscriber-route-handlers.php';
require_once 'handlers/woo-rest-route-handlers.php';
require_once 'handlers/customer-rest-route-handlers.php';
require_once 'handlers/install-handlers.php';
require_once 'handlers/mcms-options-page-handlers.php';
require_once 'handlers/cloudflare-rest-route-handlers.php';
require_once 'handlers/sync-route-handlers.php';
require_once 'handlers/stripe-route-handlers.php';
require_once 'handlers/overrides.php';
require_once 'includes/rest-routes.php';
require_once 'includes/woocomrce-routes.php';
require_once 'includes/helpers.php';
require_once 'includes/settings-page.php';
require_once 'includes/plugin-installer.php';
require_once 'includes/auth.php';
require_once 'includes/stripe-routes.php';
require_once 'middleware/auth.php';
require_once 'woo/acf.php';
require_once 'woo/tabs.php';
require_once 'includes/woocomerce-overrides.php';
require_once 'shortcodes/customer.php';

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
add_filter('wp_insert_term_data', 'Mcms\Api\RestRouteHandlers\Overrides\sanitize_term_slug', 10, 3);
add_filter('rest_prepare_attachment', 'Mcms\Api\RestRouteHandlers\Overrides\add_cloudinary_url_to_media_query', 10, 3);
add_filter('rest_pre_dispatch', 'Mcms\Middleware\Auth\api_key_check', 10, 4);


//add_filter('rest_pre_dispatch', 'Mcms\Middleware\Auth\addUserSession', 10, 3);
$post_types = get_post_types();
// Avoid a race condition where our plugin loads before woo
add_action('plugins_loaded', function () {
	if (class_exists('WooCommerce')) {

		add_action( 'init', 'wc_session_enabler' );
/*		add_action('woocommerce_before_save_product_variation', function ($variation, $i) {
			error_log(print_r('+++++++++++++', true));
		}, 10, 2);

		add_action('woocommerce_save_product_variation', function($variation_id, $i) {
			// Your code here
			error_log(print_r('*--*-*-', true));
		}, 10, 2);


		add_action('woocommerce_admin_process_variation_object', function($variation, $idx) {

		}, 10, 2);

        add_action('woocommerce_before_product_object_save', function($product, $product_id) {
            error_log(print_r($product->get_name(), true));
            $product->set_props([
                'slug' => Mcms\Api\RestRouteHandlers\Overrides\sanitizeString($product->get_name())
            ]);
        }, 10, 2);*/

	}});




function wc_session_enabler() {
	if ( is_user_logged_in() || is_admin() ) {
		return;
	}

	if ( isset(WC()->session) && ! WC()->session->has_session() ) {
		WC()->session->set_customer_session_cookie( true );
	}


}


if (in_array('sitepress-multilingual-cms/sitepress.php', get_option('active_plugins'))) {
	foreach ($post_types as $post_type) {
		add_filter('rest_prepare_' . $post_type, 'Mcms\Api\RestRouteHandlers\Overrides\add_related_languages_to_item', 10, 3);
	}
}

// By default, the product_variation post type is not public. This filter makes it public.
function make_product_variations_post_type_public($args, $post_type) {
	if ('product_variation' === $post_type) {
		$args['public'] = true;
        $args['rest_base'] = 'store/product_variations';
	}
	return $args;
}
add_filter('register_post_type_args', 'make_product_variations_post_type_public', 10, 2);
// Product attributes are hidden by default
add_filter('register_taxonomy_args', function($args, $taxonomy) {
	if (strpos($taxonomy, 'pa_') === 0) {
		$args['public'] = true;
		$args['show_in_rest'] = true;
	}
	return $args;
}, 10, 2);

add_action('admin_init', 'mcms_redirect_after_activation');





function mcms_plugin_activate() {
//	set_transient('mcms_activating', true, 5 * 60); // 5 minutes should be enough
	$mustHavePlugins = [
		'cloudinary-image-management-and-manipulation-in-the-cloud-cdn',
		'mailjet-for-wordpress',
		'markup-markdown',
		'meta-box',
		'simple-slug-translate',
		'wordpress-seo',
		'error-log-viewer',
		'all-in-one-wp-migration',
		'acf-extended',
	];
	foreach ($mustHavePlugins as $plugin) {
		\Mcms\Api\RestRouteHandlers\CloudFlare\executeShellCommand('wp plugin install ' . $plugin . ' --activate');
	}

}

function mcms_redirect_after_activation() {

	// Check if our transient is set
	if (get_transient('mcms_activating')) {
		// Delete the transient so we don't keep redirecting
		delete_transient('mcms_activating');

		// Redirect to your plugin's settings page
		wp_safe_redirect(admin_url('admin.php?page=mcms'));
		exit;
	}

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
		plugins_url( '/assets/mcms-plugin.umd.js', __FILE__ ),
		[],
		'1.1.15',
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





