<?php
namespace Mcms\Api\RestRouteHandlers\CloudFlare;
use GuzzleHttp\Client;
use WP_REST_Request;



function handle_getBuildStatus_request(WP_REST_Request $request) {
	$deployment_id = $request->get_param('id');

	return getBuildStatus($deployment_id);
}

function handle_getHistory_request(WP_REST_Request $request) {
	$page = $request->get_param('page') ?? 1;
	$account_id = get_field('cloudflare_account_id', 'option'); // Replace with your Cloudflare account ID
	$project_name = get_field('cloudflare_project_name', 'option'); // Replace with your project name

	$client = buildCloudflareHttpClient();
	$response = $client->get("accounts/$account_id/pages/projects/$project_name/deployments?page=$page&per_page=10");
	$res = json_decode($response->getBody(), true);
	return new \WP_REST_Response( $res );
}

function handle_build_request() {
	$buildMethod = get_field('cloudflare_deploy_method', 'option');

	if ($buildMethod === 'local') {
		$astroFolder = get_field('astro_folder', 'option'); // Replace with your project name
		$buildScript = get_field('build_script', 'option');

		return new \WP_REST_Response( executeShellCommand("cd $astroFolder && \"./$buildScript\"") );
	}

	$account_id = get_field('cloudflare_account_id', 'option'); // Replace with your Cloudflare account ID
	$project_name = get_field('cloudflare_project_name', 'option'); // Replace with your project name

	$client = buildCloudflareHttpClient();
	$response = $client->post("accounts/$account_id/pages/projects/$project_name/deployments");
	return new \WP_REST_Response( json_decode($response->getBody(), true) );
}


function getBuildStatus($deployment_id) {
	$account_id = get_field('cloudflare_account_id', 'option'); // Replace with your Cloudflare account ID
	$project_name = get_field('cloudflare_project_name', 'option'); // Replace with your project name


	$client = buildCloudflareHttpClient();
	$response = $client->get("accounts/$account_id/pages/projects/$project_name/deployments/$deployment_id");
	$res = json_decode($response->getBody(), true);
	return new \WP_REST_Response( $res['result'] );
}


function buildCloudflareHttpClient() {
	$api_key = get_field('cloudflare_api_key', 'option'); // Replace with your Cloudflare API key

	$client = new Client([
		'base_uri' => 'https://api.cloudflare.com/client/v4/',
		'headers' => [
			'Authorization' => 'Bearer ' . $api_key,
			'Content-Type' => 'application/json'
		]
	]);

	return $client;
}

function executeShellCommand($command) {
	$descriptors = array(
		0 => array("pipe", "r"), // stdin is a pipe that the child will read from
		1 => array("pipe", "w"), // stdout is a pipe that the child will write to
		2 => array("pipe", "w")  // stderr is a pipe that the child will write to
	);

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

		return [
			'code' => $return_value,
			'success'=> $return_value === 0,
			'output' => $output,
			'command' => $command,
			'error' => $error
		];
	}
}