<?php

header("Content-type: application/json");
echo json_encode([
	"apps" => [
		"tourbuzz" => [
			"url" => "http://buzz.fixxx.nl"
		],
		"haltecoach" => [
			"url" => "http://coach.fixxx.nl"
		]
	],
	"apis" => [
		"attracties" => [
			"origineel" => "http://www.amsterdamopendata.nl/files/Attracties.json",
			"uri" => "http://api.fixxx.nl/attracties/"
		],
		"haltes" => [
			"origineel" => "http://www.amsterdamopendata.nl/files/ivv/touringcar/in_uitstaphaltes.json",
			"uri" => "http://api.fixxx.nl/haltes/"
		],
		"parkeerplaatsen" => [
			"origineel" => "http://www.fixxx.nl/parkeerplaatsen/",
			"uri" => "http://api.fixxx.nl/parkeerplaatsen/"
		],
		"poi" => [
			"uri" => "http://api.fixxx.nl/poi/",
			"ov2" => "http://api.fixxx.nl/ov2/",
			"maps" => "https://www.google.com/maps/d/viewer?mid=z2EXMMBPPl7c.kKS6guMeeUaI"
		],
		"afstanden" => [
			"uri" => "http://api.fixxx.nl/distance/"
		],
		"pta" => [
			"uri" => "http://api.fixxx.nl/pta/?m=1",
			"arrivals" => "http://api.fixxx.nl/cruisekalender/2016/03/31"
		],
		"wegwerkzaamheden" => [
			"origineel" => "http://www.amsterdamopendata.nl/files/Projecten_Amsterdam_GeoJson.json",
			"uri" => "http://api.fixxx.nl/wegwerkzaamheden/2016/03/02"
		],
		"evenementen" => [
			"origineel" => "http://www.amsterdamopendata.nl/files/Evenementen.json",
			"uri" => "http://api.fixxx.nl/evenementen/2016/03/02"
		],
		"berichten" => [
			"uri" => "http://api.fixxx.nl/berichten/2016/03/02"
		]
	]
]);
