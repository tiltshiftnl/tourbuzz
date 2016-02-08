<?php

header("Content-type: text/plain");

$json = file_get_contents("http://api.qcommerz.nl/haltes/");
$rs = json_decode($json);
foreach ($rs->haltes as $halte) {
	echo "{$halte->coordinaten->lng},\t{$halte->coordinaten->lat},\t{$halte->haltenummer},\thalte\n";
}

$json = file_get_contents("http://api.qcommerz.nl/parkeerplaatsen/");
$rs = json_decode($json);
foreach ($rs->parkeerplaatsen as $parkeerplaats) {
	if (!$parkeerplaats->naam) $parkeerplaats->naam = $parkeerplaats->nummer;
	echo "{$parkeerplaats->coordinaten->lng},\t{$parkeerplaats->coordinaten->lat},\t{$parkeerplaats->naam},\tparkeerplaats\n";
}

$json = file_get_contents("http://api.qcommerz.nl/attracties/");
$rs = json_decode($json);
foreach ($rs->attracties as $attractie) {
	echo "{$attractie->coordinaten->lng},\t{$attractie->coordinaten->lat},\t{$attractie->naam},\tattractie\n";
}
