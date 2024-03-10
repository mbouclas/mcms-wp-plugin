<?php
namespace Mcms\Api\RestRouteHandlers\Sync;

function handle_refreshData_request() {
	$astroFolder = get_field('astro_folder', 'option'); // Replace with your project name
	$cacheFolder = $astroFolder . '/__cache';
	$command = "chmod a+x -R $cacheFolder && cd $astroFolder && npm run dump:data";

/*	exec($command, $output, $return_var);
	return new \WP_REST_Response( [
		'code' => $return_var,
		'success'=> $return_var === 0,
		'output' => $output,

	] );*/

	$descriptors = array(
		0 => array("pipe", "r"), // stdin is a pipe that the child will read from
		1 => array("pipe", "w"), // stdout is a pipe that the child will write to
		2 => array("pipe", "w")  // stderr is a pipe that the child will write to
	);

// Start the process
	$process = proc_open($command, $descriptors, $pipes);

	if (is_resource($process)) {
		// Write data to the process's stdin (if needed)
		fwrite($pipes[0], "input_data_here");

		// Close the input stream to signal the end of input
		fclose($pipes[0]);

		// Read the output from the process's stdout
		$output = stream_get_contents($pipes[1]);
		fclose($pipes[1]);

		// Read the output from the process's stderr
		$error = stream_get_contents($pipes[2]);
		fclose($pipes[2]);

		// Close the process
		$return_value = proc_close($process);

		return new \WP_REST_Response( [
			'code' => $return_value,
			'success'=> $return_value === 0,
			'output' => $output,

		] );
	}


}

