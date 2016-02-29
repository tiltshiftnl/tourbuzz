<?php

require '../vendor/autoload.php';

use Intervention\Image\ImageManager;

$fileDir = __DIR__ . "/../images/";

switch ($_SERVER["REQUEST_METHOD"]) {
	case "GET":
		$fileName = array_values(array_filter(explode("/", $_SERVER["REQUEST_URI"])))[1];
		$filePath = $fileDir . $fileName;
		if (file_exists($filePath)) {
			$manager = new ImageManager(["driver" => "imagick"]);
			$image = $manager->make($filePath);
			if (isset($_GET["greyscale"])) {
				$image->greyscale();
			}
			if (isset($_GET["method"])) {
				switch ($_GET["method"]) {
					case "fit":
						$image->fit((int) $_GET["width"], $_GET["height"]);
						break;
					case "resize":
						$image->resize((int) $_GET["width"], (int) $_GET["height"]);
						break;
				}
			}
			exit($image->response('jpg'));
		}
		exit;

	case "OPTIONS":
		header("Access-Control-Allow-Origin: *");
		header("Access-Control-Allow-Headers: X_FILENAME, Content-Type");
		exit;

	case "POST":
		$fileContents = file_get_contents('php://input');
		$fileName = sha1($fileContents);
		$filePath = $fileDir . $fileName;
		file_put_contents($filePath, $fileContents);
		header("Access-Control-Allow-Origin: *");
		exit($fileName);
}
