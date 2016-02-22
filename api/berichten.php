<?php

ini_set("error_reporting", 1);
ini_set("display_errors", E_ALL);

$filePath = "files/messages.json";

function randomHash() {
	$alphabet = "0123456789abcdefg";
	$hash = "";
	while (strlen($hash) < 40) {
		$hash .= $alphabet[mt_rand(0, strlen($alphabet))];
	}
	return $hash;
}

function loadMessages() {
	global $filePath;
	$messagesJson = file_get_contents($filePath);
	$messages = json_decode($messagesJson);
	if (!$messages) $messages = []; // FIXME backup here?
	return $messages;
}

function backupMessagesFile() {
	global $filePath;
	$backupFilePath = $filePath . ".backup." . date("Y-m-d");
	if (!file_exists($backupFilePath)) {
		copy($filePath, $backupFilePath);
	}
}

function saveMessages($messages) {
	global $filePath;
	backupMessagesFile();
	file_put_contents($filePath, json_encode($messages));
}

$messageFields = [
	"id",
	"title",
	"body",
	"title_en",
	"body_en",
	"title_fr",
	"body_fr",
	"startdate",
	"enddate",
	"category"
];

$messages = loadMessages();

switch ($_SERVER["REQUEST_METHOD"]) {
	case "POST":
		$message = [];
		foreach ($messageFields as $messageField) {
			$message[$messageField] = !empty($_POST[$messageField]) ?
				$_POST[$messageField] : "";
		}
		if (empty($message["id"])) {
			$message["id"] = randomHash();
		}
		$update = false;
		foreach ($messages as &$existingMessage) {
			if ($existingMessage->id === $message["id"]) {
				$existingMessage = $message;
				$update = true;
				break;
			}
		}
		if (!$update) $messages[] = $message;
		saveMessages($messages);
		header("Content-type: application/json");
		echo json_encode($message);
		exit;

	case "GET":
		$messages = loadMessages();
		$messages = array_map(function ($message) use ($messageFields) {
			foreach ($messageFields as $messageField) {
				if (!isset($message->{$messageField})) {
					$message->{$messageField} = "";
				}
			}
			return $message;
		}, $messages);
		uasort($messages, function ($messageA, $messageB) {
			return $messageA->startdate < $messageB->startdate;
		});
		$uriParts = array_values(array_filter(explode("/", explode("?", $_SERVER["REQUEST_URI"])[0])));
		$date = date("Y-m-d");
		if (!empty($uriParts[1]) && strlen($uriParts[1]) === 4) {
			$date = "{$uriParts[1]}-{$uriParts[2]}-{$uriParts[3]}";
			$messages = array_values(array_filter($messages, function ($message) use ($date) {
				return $message->startdate <= $date &&
				       $message->enddate >= $date;
			}));
		} else if (!empty($uriParts[1]) && strlen($uriParts[1]) === 40) {
			$id = $uriParts[1];
			foreach ($messages as $message) {
				if ($message->id !== $id) continue;
				header("Content-type: application/json");
				echo json_encode([
					"message" => $message
				]);
				exit;
			}
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
		$messages = loadMessages();
		$uriParts = array_values(array_filter(explode("/", explode("?", $_SERVER["REQUEST_URI"])[0])));
		$id = $uriParts[1];
		$ids = $id ? [$id] : $_GET["ids"];
		$messages = array_values(array_filter($messages, function ($message) use ($ids) {
			return !in_array($message->id, $ids);
		}));
		saveMessages($messages);
		exit;
}

