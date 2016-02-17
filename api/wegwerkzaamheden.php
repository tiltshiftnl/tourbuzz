<?php

$uriParts = array_values(array_filter(explode("/", $_SERVER["REQUEST_URI"])));

$year = $uriParts[1];
$month = $uriParts[2];
$day = $uriParts[3];

$todayString = "{$year}{$month}{$day}000000";

$sourceUrl = "http://www.amsterdamopendata.nl/files/Projecten_Amsterdam_GeoJson.json";
$cacheFilepath = "cache/wegwerkzaamheden.json";
if (is_file($cacheFilepath) && time() - filemtime($cacheFilepath) < 24 * 3600) {
	$fileContents = file_get_contents($cacheFilepath);
} else {
	$fileContents = file_get_contents($sourceUrl);
	file_put_contents($cacheFilepath, $fileContents);
}
$jsonData = json_decode($fileContents);
$werkzaamheden = [];
foreach ($jsonData->features as $data) {
	if ($data->properties->STARTDATUM > $todayString ||
	    $data->properties->EINDDATUM < $todayString) {
		continue;
	}
	$werk = [];
	foreach ($data->properties as $key => $value) {
		$werk[strtolower($key)] = $value;
	}
	$werk["_origineel"] = $data;
	$werkzaamheden[] = $werk;
}

header("Content-type: application/json");
echo json_encode([
	"_bron" => $sourceUrl,
	"werkzaamheden" => $werkzaamheden
]);
