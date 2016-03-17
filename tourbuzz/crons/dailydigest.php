<?php

require_once(__DIR__ . '/config.php');
if (is_file(__DIR__ . '/config_local.php')) {
    require_once(__DIR__ . '/config_local.php');
}

$res = json_decode(file_get_contents($apiUri . "/berichten/" . date("Y/m/d")));
$messages = (array) $res->messages;
$title = "Overzicht van tour buzz berichten voor " . date("d-m-Y");
$content = "Overzicht van tour buzz berichten voor " . date("d-m-Y") . ":\r\n\r\n";
foreach ($messages as $message) {
    $content .= " - {$message->title}\r\n";
}
$content .= "\r\nKijk voor het overzicht op {$buzzProc}{$buzzUri}/" . date("Y/m/d");

mail(
    $mailTo,
    $title,
    $content,
    "From: dashboard@{$buzzUri}\r\n" .
    "Reply-To: noreply@{$buzzUri}\r\n" .
    "X-Mailer: PHP/" . phpversion()
);

