<?php

//ini_set("display_errors", 1);
//ini_set("error_reporting", E_ALL);

function distance($lat1, $lng1, $lat2, $lng2) {
	$R = 6371;
	$dLat = deg2rad($lat2 - $lat1);
	$dLng = deg2rad($lng2 - $lng1);
	$a = pow(sin($dLat / 2), 2) + pow(sin($dLng / 2), 2) * cos(deg2rad($lat1)) * cos(deg2rad($lat2));
	$c = 2 * atan2(sqrt($a), sqrt(1 - $a));
	$d = $R * $c;
	return $d;
}

$lat1 = $_GET["lat1"];
$lng1 = $_GET["lng1"];
$lat2 = $_GET["lat2"];
$lng2 = $_GET["lng2"];

header("Content-type: application/json");
echo json_encode([
	"distance" => distance($lat1, $lng1, $lat2, $lng2)
]);
