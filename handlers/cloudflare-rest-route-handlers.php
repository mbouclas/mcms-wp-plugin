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