<?php
namespace Mcms\Api\RestRouteHandlers;

use function Mcms\Handlers\getOptionsPageValues;

function handle_boot_request() {
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
	$values['cssUrl'] = plugins_url( '../assets/styles.css', __FILE__ );

	return new \WP_REST_Response( ['syncSettings'=> $values] );
}

