<?php

$uriParts = array_values(array_filter(explode("/", $_SERVER["REQUEST_URI"])));

$year = $uriParts[1];
$month = $uriParts[2];
$day = $uriParts[3];

$todayString = "{$year}-{$month}-{$day}";
$todayDate = strtotime($todayString);

$sourceUrl = "http://www.amsterdamopendata.nl/files/Evenementen.json";
$cacheFilepath = "../cache/evenementen.json";
if (is_file($cacheFilepath) && time() - filemtime($cacheFilepath) < 24 * 3600) {
	$fileContents = file_get_contents($cacheFilepath);
} else {
	$fileContents = file_get_contents($sourceUrl);
	file_put_contents($cacheFilepath, $fileContents);
}
$jsonData = json_decode($fileContents);
$evenementen = [];
foreach ($jsonData as $data) {
	$startdate = strtotime($data->dates->startdate);
	$enddate = strtotime($data->dates->enddate);
	if ($startdate > $todayDate ||
	    $enddate < $todayDate) {
		continue;
	}
	$evenement = [];
	$evenement = $data;
	//foreach ($data as $key => $value) {
	//	$evenement[strtolower($key)] = $value;
	//}
	//$evenement["_origineel"] = $data;
	$evenementen[] = $evenement;
}

header("Content-type: application/json");
echo json_encode([
	"_bron" => $sourceUrl,
	"evenementen" => $evenementen
]);
