<?php

ini_set("error_reporting", 1);
ini_set("display_errors", E_ALL);

$url = "http://www.ptamsterdam.nl/local/scripts/";
$m = (int) $_GET['m'];

/**
 *
 */
function post($url, $fields) {
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url); 
	curl_setopt($ch, CURLOPT_POST, count($fields));
	curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	$htmlData = curl_exec($ch);
	curl_close($ch);
	return $htmlData;
}

/**
 *
 */
function enrichCalendarItems(&$items) {
	global $url;
	foreach ($items as &$item) {
		$item["html"] = post(
			$url . "getCalendarDetail.php",
			["id" => $item["id"]]);
	};
}

/**
 *
 */
function extractCalendarItems($htmlData) {
	$htmlDoc = new DOMDocument();
	$htmlDoc->loadHTML($htmlData);

	$xpath = new DOMXpath($htmlDoc);
	$items = [];

	$els = $xpath->query("//li");
	foreach ($els as $el) {
		$item = [
			"id" => $el->getAttribute("data")
		];
		foreach ($el->childNodes as $childEl) {
			if (is_a($childEl, "DOMText")) continue;
			if ($childEl->nodeValue) {
				$item[$childEl->getAttribute("class")] = $childEl->nodeValue;
			}
		}
		$items[] = $item;
	}
	return $items;
}

$htmlData = post(
	$url . "getCalendarMonth.php",
	["month" => (int) $_GET["m"]]);
$items = extractCalendarItems($htmlData);
enrichCalendarItems($items);

header("Content-type: application/json");
echo json_encode([
	"items" => $items
]);
