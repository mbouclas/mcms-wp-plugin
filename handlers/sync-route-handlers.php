<?php
namespace Mcms\Api\RestRouteHandlers\Sync;

function handle_refreshData_request() {
	$astroFolder = get_field('astro_folder', 'option'); // Replace with your project name
	$command = 'cd ' . $astroFolder . ' && chmod a+x -R __cache && npm run dump:data';
	exec($command, $output, $return_var);
	return new \WP_REST_Response( [
		'code' => $return_var,
		'success'=> $return_var === 0,
		'output' => $output,

	] );
}

