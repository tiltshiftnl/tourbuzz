<?php

header("Content-type: application/x-download");
header("Content-Disposition: attachment; filename=touringcar.csv");
header("Content-Transfer-Encoding: binary");

$json = file_get_contents("http://api.qcommerz.nl/haltes/");
$rs = json_decode($json);
foreach ($rs->haltes as $halte) {
	$halte->naam = str_replace(",", "/", $halte->naam);
	echo "{$halte->coordinaten->lng},\t{$halte->coordinaten->lat},\t{$halte->haltenummer},\thalte\n";
}

$json = file_get_contents("http://api.qcommerz.nl/parkeerplaatsen/");
$rs = json_decode($json);
foreach ($rs->parkeerplaatsen as $parkeerplaats) {
	if (!$parkeerplaats->naam) $parkeerplaats->naam = $parkeerplaats->nummer;
	$parkeerplaats->naam = str_replace(",", "/", $parkeerplaats->naam);
	echo "{$parkeerplaats->coordinaten->lng},\t{$parkeerplaats->coordinaten->lat},\t{$parkeerplaats->naam},\tparkeerplaats\n";
}

$json = file_get_contents("http://api.qcommerz.nl/attracties/");
$rs = json_decode($json);
foreach ($rs->attracties as $attractie) {
	$attractie->naam = str_replace(",", "/", $attractie->naam);
	echo "{$attractie->coordinaten->lng},\t{$attractie->coordinaten->lat},\t{$attractie->naam},\tattractie\n";
}
