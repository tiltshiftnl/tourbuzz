<?php

//ini_set("error_reporting", 1);
//ini_set("display_errors", E_ALL);

require_once("../vendor/autoload.php");

require_once("../config/haltes/config.php");
if (is_file("../config/haltes/config_local.php")) {
    require_once("../config/haltes/config_local.php");
}

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
        if ($msg->is_live && preg_match("/((H|h)[0-9]+) niet beschikbaar/", $msg->title, $matches)) {
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
        "beschikbaar" => empty($disabled[$haltenummer]),
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

uksort($result["haltes"], function ($a, $b) {
    return (int) substr($a, 1) > (int) substr($b, 1);
});

header("Content-type: application/json");
echo json_encode($result);
