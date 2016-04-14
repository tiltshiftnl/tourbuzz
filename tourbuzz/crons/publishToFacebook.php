<?php

require_once("../vendor/autoload.php");
require_once("config_facebook.php");

// To get the $access_token (that is in private):
// 1) go to: https://developers.facebook.com/tools/explorer
// 2) lookup: tourbuzzamsterdam?fields=access_token

// Working curl example
// * curl https://graph.facebook.com/v2.0/tourbuzzamsterdam/feed -F "access_token=xxxxx" -F "message=Morgen een nieuwe versie" -F "method=post"

$url = "https://graph.facebook.com/v2.6/tourbuzzamsterdam/feed";

$guzzle = new GuzzleHttp\Client();

try {
    $messagesUrl = "http://api.tourbuzz.nl/berichten/" . date("Y/m/d");
    $res = $guzzle->request('GET', $messagesUrl);
    $messages = json_decode($res->getBody());
    $post = "Vandaag op Tour Buzz:\n\n";
    foreach ($messages->messages as $msg) {
        $post .= " - {$msg->title}\n";
    }
    $post .= "\nKijk voor meer informatie op www.tourbuzz.nl/" . date("Y/m/d");
} catch (Exception $e) {
    mail('j.groenen@amsterdam.nl', 'Fout in Tour Buzz facebook publisher', $e->getMessage());
    exit;
}

try {
    $guzzle->request("POST", $url, [
        'form_params' => [
            "access_token" => $access_token,
            "message" => $post,
//            "link" => "http://www.tourbuzz.nl/" . date("Y/m/d"),
        ]
    ]);
} catch (Exception $e) {
    mail('j.groenen@amsterdam.nl', 'Fout in Tour Buzz facebook publisher', $e->getMessage());
    exit;
}
