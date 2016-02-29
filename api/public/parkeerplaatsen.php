<?php

$sourceUrl = "http://www.amsterdamopendata.nl/files/ivv/touringcar/parkeerplaatsen.json";
$tries = 0;
do {
	$tries++;
	$fileContents = file_get_contents($sourceUrl);
	if (!$fileContents) usleep(mt_rand(0, 10000));
} while (!$fileContents && $tries < 20);
$jsonData = json_decode($fileContents);

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
