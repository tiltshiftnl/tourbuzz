<?php

$sourceUrl = "http://www.amsterdamopendata.nl/files/ivv/touringcar/in_uitstaphaltes.json";
$fileContents = file_get_contents($sourceUrl);
$jsonData = json_decode($fileContents);

$uriParts = array_values(array_filter(explode("/", $_SERVER["REQUEST_URI"])));

$result = [
	"_uri" => $uriParts,
	"_bron" => $sourceUrl
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
	$halte = (object) [
		"haltenummer" => $haltenummer,
		"straat" => $straat,
		"locatie" => $locatie,
		"capaciteit" => $capaciteit,
		"coordinaten" => [
			"lat" => $geoJson->coordinates[1],
			"lng" => $geoJson->coordinates[0]
		],
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
