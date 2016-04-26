<?php



header("Content-type: application/json");
echo json_encode([
	"apps" => [
		"tourbuzz" => [
			"url" => "http://www.tourbuzz.nl"
		],
	],
	"apis" => [
		"berichten" => [
			"uri" => "http://api.tourbuzz.nl/berichten",
            "voorbeeld" => "http://api.tourbuzz.nl/berichten/2016/03/02",
		],
		"haltes" => [
			"origineel" => "http://www.amsterdamopendata.nl/files/ivv/touringcar/in_uitstaphaltes.json",
			"uri" => "http://api.tourbuzz.nl/haltes/"
		],
		"parkeerplaatsen" => [
			"origineel" => "http://www.fixxx.nl/parkeerplaatsen/",
			"uri" => "http://api.tourbuzz.nl/parkeerplaatsen/"
		],
		"poi" => [
			"uri" => "http://api.tourbuzz.nl/poi/",
			"ov2" => "http://api.tourbuzz.nl/ov2/",
			"maps" => "https://www.google.com/maps/d/viewer?mid=z2EXMMBPPl7c.kKS6guMeeUaI"
		],
		//"pta" => [
		//	"uri" => "http://api.fixxx.nl/pta/?m=1",
		//	"arrivals" => "http://api.fixxx.nl/cruisekalender/2016/03/31"
		//],
		//"wegwerkzaamheden" => [
		//	"origineel" => "http://www.amsterdamopendata.nl/files/Projecten_Amsterdam_GeoJson.json",
		//	"uri" => "http://api.fixxx.nl/wegwerkzaamheden/2016/03/02"
		//],
		//"evenementen" => [
		//	"origineel" => "http://www.amsterdamopendata.nl/files/Evenementen.json",
		//	"uri" => "http://api.fixxx.nl/evenementen/2016/03/02"
		//],
	]
]);
