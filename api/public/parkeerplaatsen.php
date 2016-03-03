<?php

ini_set("error_reporting", 1);
ini_set("display_errors", E_ALL);

require_once("../vendor/autoload.php");

//$sourceUrl = "http://www.amsterdamopendata.nl/files/ivv/touringcar/parkeerplaatsen.json";
$sourceUrl = "http://open.datapunt.amsterdam.nl/ivv/touringcar/parkeerplaatsen.json";

try {
    $guzzle = new GuzzleHttp\Client();
    $res = $guzzle->request('GET', $sourceUrl);
} catch (Exception $e) {
    header("HTTP/1.1 404 Not Found");
    exit;
}

$jsonData = json_decode($res->getBody());

$parkeerplaatsen = [];
foreach ($jsonData->parkeerplaatsen as $data) {
	$data = $data->parkeerplaats;
	$titleParts = explode(":", $data->title);
	$geoJson = json_decode($data->Lokatie);
	$mapsImageUrl = "https://maps.googleapis.com/maps/api/staticmap?center={$geoJson->coordinates[1]},{$geoJson->coordinates[0]}&zoom=16&size=600x300&maptype=roadmap&markers={$geoJson->coordinates[1]},{$geoJson->coordinates[0]}&key=AIzaSyA_o88ovC0-TE8YyYqtIXFQIkRPeJX2VKU";
	$mapsUrl = "https://www.google.com/maps/?q=loc:{$geoJson->coordinates[1]},{$geoJson->coordinates[0]}";
	$parkeerplaats = (object) [
		"nummer" => array_shift($titleParts),
		"naam" => trim(array_shift($titleParts)),
		"capaciteit" => intval(str_replace("maximaal ", "", $data->Busplaatsen)),
		"coordinaten" => [
			"lat" => $geoJson->coordinates[1],
			"lng" => $geoJson->coordinates[0]
		],
		"mapsImageUrl" => $mapsImageUrl,
		"mapsUrl" => $mapsUrl,
		"_origineel" => $data
	];
	$parkeerplaatsen[] = $parkeerplaats;
}

header("Content-type: application/json");
echo json_encode([
	"_bron" => $sourceUrl,
	"_pogingen" => $tries,
	"parkeerplaatsen" => $parkeerplaatsen
]);
