<?php

//ini_set("error_reporting", 1);
//ini_set("display_errors", E_ALL);

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
function enrichItem(&$item) {
	$htmlDoc = new DOMDocument();
	$htmlDoc->loadHTML($item["html"]);

	$xpath = new DOMXpath($htmlDoc);

	$els = $xpath->query("//*[@class='calendar-detail-content']/ul/li");
	foreach ($els as $el) {
		$keyval = explode(":", $el->nodeValue);
		$keyval[0] = trim(strtolower($keyval[0]));
		$keyval[1] = trim(implode(":", array_slice($keyval, 1)));
		if (!empty($keyval[1])) {
			$item[$keyval[0]] = $keyval[1];
		}
	}
	unset($item["html"]);
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
		enrichItem($item);
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

/**
 *
 */
function extractCalendarXSItems($htmlData) {
	$htmlDoc = new DOMDocument();
	$htmlDoc->loadHTML($htmlData);

	$xpath = new DOMXpath($htmlDoc);
	$items = [];

	$els = $xpath->query("//*[starts-with(@id, 'calendar-items-xs-month-')]//li");
	foreach ($els as $el) {
		preg_match("/\([0-9]*/", $el->getAttribute("onclick"), $matches);
		$item = [
			"id" => (int) str_replace("(", "", $matches[0]),
			"date" => trim(explode(" ", trim($el->nodeValue))[0]),
			"name" => trim(implode(" ", array_slice(explode(" ", trim($el->nodeValue)), 1)))
		];
		$items[] = $item;
	}

	return $items;
}

if (!empty($_GET["m"])) {
	$htmlData = post(
		$url . "getCalendarMonth.php",
		["month" => (int) $_GET["m"]]);
	$items = extractCalendarItems($htmlData);
	enrichCalendarItems($items);
}

if (!empty($_GET["y"])) {
	$htmlData = post(
		$url . "getCalendarMonthsXS.php",
		["year" => (int) $_GET["y"]]);
	$items = extractCalendarXSItems($htmlData);
	enrichCalendarItems($items);
}

header("Content-type: application/json");
echo json_encode([
	"items" => $items
]);
