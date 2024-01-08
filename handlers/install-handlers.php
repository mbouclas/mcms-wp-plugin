<?php
namespace Mcms\Install;

use \JsonMachine\Items;

function handle_install_car_data_request() {
	ini_set('memory_limit', '2048M'); // Increase PHP memory limit
	$jsonFilePath = __DIR__ . '/../data/all_trims.json';
	$data = Items::fromFile($jsonFilePath);
	$groupedByMaker = [];
	$chunkSize = 100; // Size of each chunk
	$totalSize = iterator_count($data); // Total number of items

	foreach ($data as $item) {
		$maker = $item->Maker; // or $item['Maker'] if your data are associative arrays

		if (!isset($groupedByMaker[$maker])) {
			$groupedByMaker[$maker] = [];
		}

		$groupedByMaker[$maker][] = $item;

		// Yield each maker's array one by one
		//yield $maker => $groupedByMaker[$maker];
	}
/*	for ($i = 0; $i < $totalSize; $i += $chunkSize) {
		$chunk = array_slice($data->, $i, $chunkSize);
		foreach (groupByMaker($chunk) as $maker => $items) {
			echo "Maker: $maker\n";
			print_r($items); // Outputs items for this particular maker
		}
	}*/

/*	foreach (groupByMaker($data) as $maker => $items) {
		echo "Maker: $maker\n";
		print_r($items); // Outputs items for this particular maker
	}*/

	return array_map(function ($item) {
		$groupByModel = [];
		foreach ($item as $trim) {
			$model = $trim->Genmodel;
			if (!isset($groupByModel[$model])) {
				$groupByModel[$model] = [];
			}
			$groupByModel[$model][] = $trim;
		}

		$trims = unique_multidimensional_array($item, 'Genmodel_ID');
		return [
			'maker' => $item[0]->Maker,
			'count' => count($trims),
			'trims' => array_map(function ($model) {
				return ['model' => $model->Genmodel, 'id' => $model->Genmodel_ID];
			}, $trims),
		];
		}, $groupedByMaker);

}

function unique_multidimensional_array($array, $key) {
	$temp_array = array();
	$key_array = array();

	foreach($array as $val) {
		if (!in_array($val->$key, $key_array)) {
			$key_array[] = $val->$key;
			$temp_array[] = $val;
		}
	}
	return $temp_array;
}

