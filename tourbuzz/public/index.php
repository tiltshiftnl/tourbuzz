<?php

ini_set("error_reporting", E_ALL);
ini_set("display_errors", 1);

session_cache_limiter(false);
session_start();

require "../vendor/autoload.php";

/**
 * Twig plugin inladen.
 */
$app = new \Slim\Slim(["view" => new \Slim\Views\Twig()]);
$view = $app->view();
$view->setTemplatesDirectory("../app/views");
$view->parserExtensions = [new \Slim\Views\TwigExtension()];

function translate($msg) {
    $translationsJson = file_get_contents("../app/translations/translations.json");
    $translations = json_decode($translationsJson);
    return !empty($translations->translations->$msg) && !empty($translations->translations->$msg->{$_SESSION['lang']}) ?
        $translations->translations->$msg->{$_SESSION['lang']} :
        $msg;
}

function maand($m) {

    if ( empty($m) ) {
        return "Geen datum";
    }
    $month = array (
        'jan',
        'feb',
        'mrt',
        'apr',
        'mei',
        'jun',
        'jul',
        'aug',
        'sep',
        'okt',
        'nov',
        'dec'
    );

    return translate($month[(int)$m - 1]);
}

function markerLabel($index) {

    $labels = str_split('ABCDEFGHIJKLMNOPQRSTUVWXYZ');

    if ($index > 9) {
        return $labels[$index-10];
    }
    return $index;
}

function insertLinks($text) {
    $text = preg_replace(
        "/((H|h)[0-9]+)/",
        "<a href=\"/haltes/$1\" class=\"halte-link\">$1</a>",
        $text);
    $text = preg_replace(
        "/((P|p)[0-9]+)/",
        "<a href=\"/parkeerplaatsen/$1\" class=\"parkeerplaats-link\">$1</a>",
        $text);
    $text = preg_replace(
        "/([^\s]+@([^\s]+\.)+[^\s\.]+)/",
        "<a href=\"mailto:$1\">$1</a>",
        $text);
    return $text;
}

$twig = $app->view()->getEnvironment();
$twig->addFunction('__', new Twig_Function_Function('translate'));
$twig->addFunction('maand', new Twig_Function_Function('maand'));
$twig->addFunction('insertlinks', new Twig_Function_Function('insertLinks'));
$twig->addFunction('markerlabel', new Twig_Function_Function('markerLabel'));

require_once "routes.php"; // System routes

require_once "../app/routes.php"; // Project routes

/**
 * Run!
 */
$app->run();


