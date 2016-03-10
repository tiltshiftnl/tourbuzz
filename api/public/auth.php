<?php

ini_set("error_reporting", 1);
ini_set("display_errors", E_ALL);

$passwords = json_decode(file_get_contents("../files/passwords.json"));
$tokens = json_decode(file_get_contents("../files/tokens.json"));
$maxAge = 3600 * 8;

function createPrefix() {
    $alphabet = './' . implode("", array_merge(range("0", "9"), range ("A", "Z"), range("a", "z")));
    $salt = "";
    while (strlen($salt) < 23) $salt .= $alphabet[mt_rand(0, strlen($alphabet))];
    return '$2a$12$' . $salt;
}

function createToken() {
    $alphabet = implode("", array_merge(range("0", "9"), range("a", "f")));
    $token = "";
    while (strlen($token) < 40) $token .= $alphabet[mt_rand(0, strlen($alphabet))];
    return $token;
}

switch ($_SERVER["REQUEST_METHOD"]) {
    case "POST":
        if (empty($_POST["username"]) || empty($_POST["password"])) {
            if (!empty($_POST["password"])) {
                $prefix = createPrefix();
                $encrypted = crypt($_POST["password"], $prefix);
                header("Content-type: application/json");
                exit(json_encode([
                    "encrypted" => $encrypted
                ]));
            }
            header("HTTP/1.1 400 Bad Request");
            exit;
        }
        $username = $_POST["username"];
        $password = $_POST["password"];
        if (empty($passwords->{$username})) {
            header("HTTP/1.1 404 Not Found");
            exit;
        }
        $stored = $passwords->{$username};
        $storedPrefix = substr($stored, 0, 29);
        $encrypted = crypt($password, $storedPrefix);
        if ($encrypted !== $stored) {
            header("HTTP/1.1 404 Not Found");
            exit;
        }
        $token = createToken();
        $tokens->{$token} = [
            "username" => $username,
            "timestamp" => time()
        ];
        file_put_contents("../files/tokens.json", json_encode($tokens));
        header("Content-type: application/json");
        exit(json_encode([
            "token" => $token
        ]));
        break;

    case "GET":
        if (empty($_GET["token"])) {
            header("HTTP/1.1 400 Bad Request");
            exit;
        }
        $token = $_GET["token"];
        if (empty($tokens->{$token})) {
            header("HTTP/1.1 404 Not Found");
            exit;
        }
        header("Content-type: application/json");
        $username = $tokens->{$token}->username;
        $age = time() - $tokens->{$token}->timestamp;
        $expires = $maxAge - $age;
        if ($expires < 0) {
            //FIXME remove from tokens.
            header("HTTP/1.1 404 Not Found");
            exit;
        }
        exit(json_encode([
            "username" => $username,
            "expires" => $maxAge - $age
        ]));
        break;
}
