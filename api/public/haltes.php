<?php

ini_set("error_reporting", 1);
ini_set("display_errors", E_ALL);

require_once("../vendor/autoload.php");

//$sourceUrl = "http://www.amsterdamopendata.nl/files/ivv/touringcar/in_uitstaphaltes.json";
//$sourceUrl = "http://data.amsterdam.nl/files/ivv/touringcar/in_uitstaphaltes.json";
$sourceUrl = "http://open.datapunt.amsterdam.nl/ivv/touringcar/in_uitstaphaltes.json";

try {
    $guzzle = new GuzzleHttp\Client();
    $res = $guzzle->request('GET', $sourceUrl);
} catch (Exception $e) {
    header("HTTP/1.1 404 Not Found");
    exit;
}

$jsonData = json_decode($res->getBody());

$uriParts = array_values(array_filter(explode("/", $_SERVER["REQUEST_URI"])));

$result = [
	"_uri" => $uriParts,
	"_bron" => $sourceUrl,
	"_pogingen" => $tries
];

foreach ($jsonData->in_uitstaphaltes as $data) {
	$data = $data->in_uitstaphalte;
	$titleParts = explode(":", $data->title);
	$haltenummer = array_shift($titleParts);
	if (!preg_match('/^H[0-9]{1,2}$/', $haltenummer)) continue;
	$straat = trim(array_shift($titleParts));
	$geoJson = json_decode($data->Lokatie);
	$locatie = trim($data->Bijzonderheden);
	$capaciteit = intval($data->Busplaatsen);
	$mapsImageUrl = "https://maps.googleapis.com/maps/api/staticmap?center={$geoJson->coordinates[1]},{$geoJson->coordinates[0]}&zoom=16&size=600x300&maptype=roadmap&markers={$geoJson->coordinates[1]},{$geoJson->coordinates[0]}&key=AIzaSyA_o88ovC0-TE8YyYqtIXFQIkRPeJX2VKU";
	$mapsUrl = "https://www.google.com/maps/?q=loc:{$geoJson->coordinates[1]},{$geoJson->coordinates[0]}";
	$halte = (object) [
		"haltenummer" => $haltenummer,
		"straat" => $straat,
		"locatie" => $locatie,
		"capaciteit" => $capaciteit,
		"location" => [
			"lat" => $geoJson->coordinates[1],
			"lng" => $geoJson->coordinates[0]
		],
		"mapsImageUrl" => $mapsImageUrl,
		"mapsUrl" => $mapsUrl,
		"_origineel" => $data
	];
	if (!empty($uriParts[1])) {
		if (strtolower($haltenummer) !== strtolower($uriParts[1])) {
			continue;
		} else {
			$result["halte"] = $halte;
			break;
		}
	} else {
		$result["haltes"][$halte->haltenummer] = $halte;
	}
}

header("Content-type: application/json");
echo json_encode($result);
