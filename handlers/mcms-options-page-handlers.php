<?php
namespace Mcms\Handlers\OptionsPage;

function mcms_options_page() {
	add_menu_page(
		'Mcms Dashboard',
		'Mcms',
		'manage_options',
		'mcms',
		'Mcms\Handlers\OptionsPage\mcms_dashboard_page_html',
		plugin_dir_url( __FILE__ ) . 'images/icon_mcms.png',
		20
	);
}

function mcms_dashboard_page_html() {
	// check user capabilities
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	// get the settings values
/*	$mcms_setting_url = get_field('site-url', 'option');
	$mcms_setting_client = get_option( 'mcms_setting_client' );
	$mcms_setting_repeat = get_option( 'mcms_setting_repeat' );
	$mcms_setting_key = get_option( 'mcms_setting_key' );*/
	?>

	<sync-app></sync-app>

	<?php
}