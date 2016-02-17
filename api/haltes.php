<?php

$sourceUrl = "http://www.amsterdamopendata.nl/files/ivv/touringcar/in_uitstaphaltes.json";
$tries = 0;
do {
	$tries++;
	$fileContents = file_get_contents($sourceUrl);
	if (!$fileContents) usleep(mt_rand(0, 10000));
} while (!$fileContents);
$jsonData = json_decode($fileContents);

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
		"coordinaten" => [
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
		$result["haltes"][] = $halte;
	}
}

header("Content-type: application/json");
echo json_encode($result);
