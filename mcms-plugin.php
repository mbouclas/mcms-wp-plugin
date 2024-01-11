<?php
/*
Plugin Name: Mcms Plugin
Plugin URI: http://URI_Of_Page_Describing_Plugin_and_Updates
Description: A brief description of the Plugin.
Version: 1.3.1
Author: mbouclas
Author URI: http://URI_Of_The_Plugin_Author
License: A "Slug" license name e.g. GPL2
*/
require __DIR__ . '/vendor/autoload.php';

$client = new \GuzzleHttp\Client();
use Mcms\Includes\Test;
use Cocur\Slugify\Slugify;

require_once 'handlers/rest-route-handlers.php';
require_once 'handlers/api-rest-route-handlers.php';
require_once 'handlers/install-handlers.php';
require_once 'handlers/mcms-options-page-handlers.php';
require_once 'handlers/cloudflare-rest-route-handlers.php';
require_once 'handlers/sync-route-handlers.php';
require_once 'includes/rest-routes.php';
require_once 'includes/settings-page.php';

// register the plugin
register_activation_hook( __FILE__, 'mcms_plugin_activate' );
register_deactivation_hook( __FILE__, 'mcms_plugin_deactivate' );

add_action( 'admin_menu', 'Mcms\Handlers\OptionsPage\mcms_options_page' );
add_action( 'admin_enqueue_scripts', 'enque_mcms_scripts' );
add_action('admin_enqueue_scripts', 'enqueue_admin_styles');
add_action('admin_footer', 'add_custom_html_to_admin_footer', 999);
add_action('admin_bar_menu', 'add_toolbar_button', 999);
add_action('add_meta_boxes', 'add_mcms_controls');
add_filter('script_loader_tag', 'add_type_attribute' , 10, 3);
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
		'1.1.9',
		[
			'in_footer' => true,
			'type' => 'module'
		]
	);

}

function enqueue_admin_styles() {
	wp_enqueue_style( 'mcms-styles', plugins_url( '/assets/styles.css', __FILE__ ), false, '1.1.9' );
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

add_filter('wp_insert_post_data', 'sanitize_post_slug', 10, 2);
add_filter('wp_update_term_data', 'sanitize_term_slug', 10, 3);
function sanitize_post_slug($data, $postarr) {
	$slugify = new Slugify(['lowercase' => true]);
	$slugify->activateRuleSet('greek');

	if (empty($data['post_name']) || strpos($data['post_name'], 'auto-draft') === 0) {
        $data['post_name'] = $slugify->slugify($data['post_title']);
        echo $data['post_name'];
    }


	return $data;
}


function sanitize_term_slug($data, $term_id, $taxonomy) {
	$slugify = new Slugify(['lowercase' => true]);
    $slugify->activateRuleSet('greek');
    if (empty($data['slug'])) {
        $data['slug'] = $slugify->slugify($data['name']);
    }

	return $data;
}