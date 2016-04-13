<?php

//ini_set("error_reporting", 1);
//ini_set("display_errors", E_ALL);

require_once("../vendor/autoload.php");

//$sourceUrl = "http://www.amsterdamopendata.nl/files/ivv/touringcar/parkeerplaatsen.json";
$sourceUrl = "http://open.datapunt.amsterdam.nl/ivv/touringcar/parkeerplaatsen.json";
$messagesUrl = "http://api.tourbuzz.nl/berichten/" . date("Y/m/d");

$guzzle = new GuzzleHttp\Client();

try {
    $res = $guzzle->request('GET', $sourceUrl);
    $jsonData = json_decode($res->getBody());
} catch (Exception $e) {
    header("HTTP/1.1 404 Not Found");
    exit;
}

$disabled = [];
try {
    $res = $guzzle->request('GET', $messagesUrl);
    $messages = json_decode($res->getBody());
    foreach ($messages->messages as $msg) {
        if (preg_match("/((P|p)[0-9]+) niet beschikbaar/", $msg->title, $matches)) {
            $disabled[$matches[1]] = $matches[1];
        }
    }
} catch (Exception $e) {
    // void ignore
}

$uriParts = array_values(array_filter(explode("/", $_SERVER["REQUEST_URI"])));

$result = [
    "_datum" => date("Y-m-d"),
	"_uri" => $uriParts,
	"_bron" => $sourceUrl,
	"_pogingen" => $tries,
];

foreach ($jsonData->parkeerplaatsen as $data) {
	$data = $data->parkeerplaats;
	$titleParts = explode(":", $data->title);
	$geoJson = json_decode($data->Lokatie);
	$mapsImageUrl = "https://maps.googleapis.com/maps/api/staticmap?center={$geoJson->coordinates[1]},{$geoJson->coordinates[0]}&zoom=16&size=600x300&maptype=roadmap&markers={$geoJson->coordinates[1]},{$geoJson->coordinates[0]}&key=AIzaSyA_o88ovC0-TE8YyYqtIXFQIkRPeJX2VKU";
	$mapsUrl = "https://www.google.com/maps/?q=loc:{$geoJson->coordinates[1]},{$geoJson->coordinates[0]}";
    $nummer = array_shift($titleParts);
	$parkeerplaats = (object) [
		"nummer" => $nummer,
		"naam" => trim(array_shift($titleParts)),
		"capaciteit" => intval(str_replace("maximaal ", "", $data->Busplaatsen)),
		"location" => [
			"lat" => $geoJson->coordinates[1],
			"lng" => $geoJson->coordinates[0]
		],
		"mapsImageUrl" => $mapsImageUrl,
		"mapsUrl" => $mapsUrl,
        "beschikbaar" => empty($disabled[$nummer]),
		"_origineel" => $data
	];
	if (!empty($uriParts[1])) {
		if (strtolower($parkeerplaats->nummer) !== strtolower($uriParts[1])) {
			continue;
		} else {
			$result["parkeerplaats"] = $parkeerplaats;
			break;
		}
	} else {
		$result["parkeerplaatsen"][$parkeerplaats->nummer] = $parkeerplaats;
	}
}

uksort($result["parkeerplaatsen"], function ($a, $b) {
    return (int) substr($a, 1) > (int) substr($b, 1);
});

header("Content-type: application/json");
echo json_encode($result);
