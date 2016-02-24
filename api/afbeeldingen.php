<?php

$fileDir = __DIR__ . "/images/";

switch ($_SERVER["REQUEST_METHOD"]) {
	case "GET":
		$fileName = array_values(array_filter(explode("/", $_SERVER["REQUEST_URI"])))[1];
		$filePath = $fileDir . $fileName;
		if (file_exists($filePath)) {
			header("Content-type: image/jpeg");
			readfile($filePath);
		}
		break;

	case "POST":
		$fileContents = file_get_contents('php://input');
		$fileName = sha1($fileContents);
		$filePath = $fileDir . $fileName;
		file_put_contents($filePath, $fileContents);
		exit($fileName);
}
