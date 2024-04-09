<?php
namespace Mcms\Api\RestRouteHandlers;

use function Mcms\Handlers\getOptionsPageValues;

function handle_boot_request() {
	if (get_transient('mcms_activating')) {
		return new \WP_REST_Response( [
			'installingPlugins' => true,
		] );
	}

	$ret = [];
	$fields = acf_get_fields('group_sync_settings');
	$values = [];
	foreach ($fields as $field) {
		if (in_array($field['type'], ['tab', 'group', 'repeater'])) {
			continue;
		}
		$values[$field['name']] = get_field($field['name'], 'option');
	}

	$values['adminUrl'] = get_admin_url();

	$values['cssUrl'] = plugins_url( 'mcms-plugin/assets/styles.css' );

	return new \WP_REST_Response( ['syncSettings'=> $values] );
}

