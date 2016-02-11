<?php

$uriParts = array_values(array_filter(explode("/", $_SERVER["REQUEST_URI"])));

$year = $uriParts[1];
$month = $uriParts[2];
$day = $uriParts[3];

$sourceUrl = "http://api.qcommerz.nl/pta/?y={$year}";

$cacheFilepath = "cache/pta_{$year}.json";
if (is_file($cacheFilepath) && time() - filemtime($cacheFilepath) < 24 * 3600) {
	$fileContents = file_get_contents($cacheFilepath);
} else {
	$fileContents = file_get_contents($sourceUrl);
	file_put_contents($cacheFilepath, $fileContents);
}


$contents = json_decode($fileContents);
$items = array_filter($contents->items, function ($data) use ($day, $month, $year) {
	return $data->date === "{$day}-{$month}-{$year}";
});


header("Content-type: application/json");
echo json_encode([
	"items" => $items
]);
