<?php
$filePath = "files/messages.json";

function randomHash() {
	$alphabet = "0123456789abcdefg";
	$hash = "";
	while (strlen($hash) < 40) {
		$hash .= $alphabet[mt_rand(0, strlen($alphabet))];
	}
	return $hash;
}

switch ($_SERVER["REQUEST_METHOD"]) {
	case "POST":
		$title = $_POST["title"];
		$body = $_POST["body"];
		$startdate = $_POST["startdate"];
		$enddate = $_POST["enddate"];
		$message = [
			"id" => randomHash(),
			"title" => $title,
			"body" => $body,
			"startdate" => $startdate,
			"enddate" => $enddate
		];
		$messagesJson = file_get_contents($filePath);
		$messages = json_decode($messagesJson);
		if (!$messages) $messages = [];
		$messages[] = $message;
		file_put_contents($filePath, json_encode($messages));
		exit;

	case "GET":
		$messagesJson = file_get_contents($filePath);
		$messages = json_decode($messagesJson);
		$uriParts = array_values(array_filter(explode("/", explode("?", $_SERVER["REQUEST_URI"])[0])));
		$date = date("Y-m-d");
		if (!empty($uriParts[1])) {
			$date = "{$uriParts[1]}-{$uriParts[2]}-{$uriParts[3]}";
			$messages = array_values(array_filter($messages, function ($message) use ($date) {
				return $message->startdate <= $date &&
				       $message->enddate >= $date;
			}));
		}
		header("Content-type: application/json");
		echo json_encode([
			"_date" => $date,
			"_nextDate" => date("Y-m-d", strtotime("+1 day", strtotime($date))), 
			"_prevDate" => date("Y-m-d", strtotime("-1 day", strtotime($date))), 
			"messages" => $messages
		]);
		exit;

	case "DELETE":
		$uriParts = array_values(array_filter(explode("/", explode("?", $_SERVER["REQUEST_URI"])[0])));
		$id = $uriParts[1];
		$ids = $id ? [$id] : $_GET["ids"];
		$messagesJson = file_get_contents($filePath);
		$messages = json_decode($messagesJson);
		$messages = array_values(array_filter($messages, function ($message) use ($ids) {
			return !in_array($message->id, $ids);
		}));
		file_put_contents($filePath, json_encode($messages));
		exit;	
}

