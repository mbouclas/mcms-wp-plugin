<?php
namespace Mcms\Includes\Auth;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class Auth {
	static $jwtSecretKey = 'asdar-rwqer-1234-1234';
	static $expireTime = (60 * 60 * 24 * 30); // jwt valid for 60 seconds from the issued time
}

function generate_jwt($user_id) {
	$issuedAt = time();
	$expirationTime = $issuedAt + Auth::$expireTime; // jwt valid for 60 seconds from the issued time
	$payload = array(
		'userid' => $user_id,
		'iat' => $issuedAt,
		'exp' => $expirationTime
	);
	$key = Auth::$jwtSecretKey; // change this to your own secret key
	$alg = 'HS256'; // change this to your preferred algorithm
	$jwt = JWT::encode($payload, $key, $alg);
	return $jwt;
}

function validate_jwt($jwt) {
	$key = Auth::$jwtSecretKey; // change this to your own secret key
	try {
		$decoded = JWT::decode($jwt, new Key($key, 'HS256'));

		return $decoded->userid;
	} catch (\Exception $e) {
		return [
			'error' => $e->getMessage()
		];
		return false;
	}
}