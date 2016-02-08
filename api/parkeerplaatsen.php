<?php

$sourceUrl = "http://www.amsterdamopendata.nl/files/ivv/touringcar/parkeerplaatsen.json";
$fileContents = file_get_contents($sourceUrl);
$jsonData = json_decode($fileContents);

$parkeerplaatsen = [];
foreach ($jsonData->parkeerplaatsen as $data) {
	$data = $data->parkeerplaats;
	$titleParts = explode(":", $data->title);
	$geoJson = json_decode($data->Lokatie);
	$halte = (object) [
		"nummer" => array_shift($titleParts),
		"naam" => trim(array_shift($titleParts)),
		"capaciteit" => intval(str_replace("maximaal ", "", $data->Busplaatsen)),
		"coordinaten" => [
			"lat" => $geoJson->coordinates[1],
			"lng" => $geoJson->coordinates[0]
		],
		"_origineel" => $data
	];
	$haltes[] = $halte;
}

header("Content-type: application/json");
echo json_encode([
	"_bron" => $sourceUrl,
	"parkeerplaatsen" => $haltes
]);
