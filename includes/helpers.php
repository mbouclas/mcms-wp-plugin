<?php
namespace Mcms\Includes\Helpers;

use Cocur\Slugify\Slugify;

function formatOptionValues($value) {
	if (is_serialized($value)) {
		$val = unserialize($value);
		if (is_array($val)) {
			foreach ($val as $key => $v) {
				$val[$key] = formatOptionValues( $v );
			}
			return $val;
		}

		return $val;
	}

	if (is_array($value)) {
		foreach ($value as $key => $val) {
			$value[$key] = formatOptionValues($val);
		}
	}

	if ($value === 'yes') {
		return true;
	}

	if ($value === 'no') {
		return false;
	}

	return $value;

}

function emailToUsername($email, $stringToAttach = []) {
	$slugify = new Slugify(['lowercase' => true]);
	$parts = explode('@', $email);
	$firstPart = $parts[0];


	return $slugify->slugify($firstPart. '_'.join('_', $stringToAttach));
}