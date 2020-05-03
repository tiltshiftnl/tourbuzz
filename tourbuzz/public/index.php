<?php

//ini_set("error_reporting", E_ALL);
//ini_set("display_errors", 1);

session_cache_limiter(false);
session_start();

require "../vendor/autoload.php";


/**
 * Load Twig plugin.
 */
$app = new \Slim\Slim(["view" => new \Slim\Views\Twig()]);
$view = $app->view();
$view->setTemplatesDirectory("../app/views");
$view->parserExtensions = [new \Slim\Views\TwigExtension()];

function translate($msg, $lang = null) {
    $lang = $lang === null ? $_SESSION['lang'] : $lang;
    $translationsJson = file_get_contents("../app/translations/translations.json");

    // Fixes UTF-8 conversion issues.
    $translationsJson =  mb_convert_encoding($translationsJson, 'UTF-8', mb_detect_encoding($translationsJson, 'UTF-8, ISO-8859-1', true));

    $translations = json_decode($translationsJson);
    return !empty($translations->translations->$msg) && !empty($translations->translations->$msg->{$lang}) ?
        $translations->translations->$msg->{$lang} :
        $msg;
}


/**
 * Returns translated month name for input month number.
 */
function month($m) {

    if ( empty($m) ) {
        return "Geen datum";
    }
    $month = array (
        'januari',
        'februari',
        'maart',
        'april',
        'mei',
        'juni',
        'juli',
        'augustus',
        'september',
        'oktober',
        'november',
        'december'
    );

    return translate($month[(int)$m - 1]);
}


/**
 * Replace urls in text with web links.
 */
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
$twig->addFunction('maand', new Twig_Function_Function('month'));
$twig->addFunction('insertlinks', new Twig_Function_Function('insertLinks'));

/**
 * Quick fix for route dump
 */
Class RouteDumper extends \Slim\Router {
    public static function getAllRoutes() {
        $slim = \Slim\Slim::getInstance();
        return $slim->router->routes;
    }
}

function getData($fileName) {
    if (file_exists("../app/data/".$fileName)) {
        $json = file_get_contents("../app/data/".$fileName);
        $data = json_decode($json, true);
        return $data;
    }
    return false;
}

function RDtoWGS84( $x, $y ) {
    $x = floatval($x);
    $y = floatval($y);
    $u = 1* ($x - 155000) * 0.00001;
    $v = 1* ($y - 463000) * 0.00001;
    $NB = 3235.65389 * $v                    +
           -32.58297 * pow($u,2)             +
            -0.24750 * pow($v,2)             +
            -0.84978 * pow($u,2) * $v        +
            -0.06550 * pow($v,3)             +
            -0.01709 * pow($u,2) * pow($v,2) +
            -0.00738 * $u                    +
             0.00530 * pow($u,4)             +
            -0.00039 * pow($u,2) * pow($v,3) +
             0.00033 * pow($u,4) * $v        +
            -0.00012 * $u * $v;
    $NB = ($NB / 3600) + 52.15517440;
    $OL = 5260.52916 * $u                    +
           105.94684 * $u * $v               +
             2.45656 * $u * pow($v,2)        +
            -0.81885 * pow($u,3)             +
             0.05594 * $u * pow($v,3)        +
            -0.05607 * pow($u,3) * $v        +
             0.01199 * $v                    +
            -0.00256 * pow($u,3) * pow($v,2) +
             0.00128 * $u * pow($v,4)        +
             0.00022 * pow($v,2)             +
            -0.00022 * pow($u,2)             +
             0.00026 * pow($u,5);
    $OL = ($OL / 3600) + 5.38720621;
    //return array('lat'=>$NB, 'lng'=>$OL);
    return array($OL,$NB);
}

require_once "../app/bootstrap.php"; // Bootstrap
require_once "routes.php"; // System routes
require_once "../app/routes/index.php"; // Project routes

/**
 * Run!
 */
$app->run();

