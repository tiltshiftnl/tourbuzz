<?php

header("Content-type: application/x-download");
header("Content-Disposition: attachment; filename=touringcar.csv");
header("Content-Transfer-Encoding: binary");

$json = file_get_contents("http://api.tourbuzz.nl/haltes/");
$rs = json_decode($json);
foreach ($rs->haltes as $halte) {
	$halte->naam = str_replace(",", "/", $halte->naam);
	echo "{$halte->location->lng},{$halte->location->lat},{$halte->haltenummer},halte\n";
}

$json = file_get_contents("http://api.tourbuzz.nl/parkeerplaatsen/");
$rs = json_decode($json);
foreach ($rs->parkeerplaatsen as $parkeerplaats) {
	if (!$parkeerplaats->naam) $parkeerplaats->naam = $parkeerplaats->nummer;
	$parkeerplaats->naam = str_replace(",", "/", $parkeerplaats->naam);
	echo "{$parkeerplaats->location->lng},{$parkeerplaats->location->lat},{$parkeerplaats->naam},parkeerplaats\n";
}

