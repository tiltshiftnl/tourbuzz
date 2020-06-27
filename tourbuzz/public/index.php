<?php

//ini_set("error_reporting", E_ALL);
//ini_set("display_errors", 1);

session_cache_limiter(false);
session_start();

require "../vendor/autoload.php";
use Dflydev\DotAccessData\Data;

/**
 * Load Twig plugin.
 */
$app = new \Slim\Slim(["view" => new \Slim\Views\Twig()]);
$view = $app->view();
$view->setTemplatesDirectory("../app/views");
$view->parserExtensions = [new \Slim\Views\TwigExtension()];

function translate($msg, $options = null, $lang = null) {
    $lang = $lang === null ? $_SESSION['lang'] : $lang;
    // Default language
    $lang = $lang === null ? 'en' : $lang;

    if (file_exists("../app/translations/messages_{$lang}.json")) {
        $translationsJson = file_get_contents("../app/translations/messages_{$lang}.json");
    } else {
        $translationsJson = file_get_contents("../app/translations/messages_en.json");
    }

    // Fixes UTF-8 conversion issues.
    $translationsJson =  mb_convert_encoding($translationsJson, 'UTF-8', mb_detect_encoding($translationsJson, 'UTF-8, ISO-8859-1', true));

    $translations = json_decode($translationsJson, TRUE);
    $data = new Data($translations);

    if(gettype($msg) == 'string'){
        $hit = $data->get($msg);

        // Translation with string replace
        if(is_array($options)){
            return !empty($hit) && !is_array($hit) ? vsprintf($hit, $options) : "[i18n.{$lang}]: " .$msg;
        }

        // Todo implement pluralization
        return !empty($hit) && !is_array($hit) ? $hit : "[i18n.{$lang}]: " .$msg;
        
    }
}


/**
 * Returns translated month name for input month number.
 */
function month($m) {

    if ( empty($m) ) {
        return "no_date";
    }
    $month = array (
        'january',
        'february',
        'march',
        'april',
        'may',
        'june',
        'july',
        'august',
        'september',
        'october',
        'november',
        'december'
    );

    return translate("months." . $month[(int)$m - 1]);
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
$twig->addGlobal('TOURBUZZ_URI_PROTOCOL', getenv('TOURBUZZ_URI_PROTOCOL'));
$twig->addGlobal('TOURBUZZ_URI', getenv('TOURBUZZ_URI'));
$twig->addGlobal('TOURBUZZ_API_URI_PROTOCOL', getenv('TOURBUZZ_API_URI_PROTOCOL'));
$twig->addGlobal('TOURBUZZ_API_URI', getenv('TOURBUZZ_API_URI'));
$twig->addGlobal('TOURINGCAR_URI_PROTOCOL', getenv('TOURINGCAR_URI_PROTOCOL'));
$twig->addGlobal('TOURINGCAR_URI_PROTOCOL', getenv('TOURINGCAR_URI_PROTOCOL'));
$twig->addGlobal('TOURINGCAR_URI', getenv('TOURINGCAR_URI'));
$twig->addGlobal('TOURINGCAR_CONTACT_EMAIL', getenv('TOURINGCAR_CONTACT_EMAIL'));
$twig->addGlobal('TOURINGCAR_CONTACT_NAME', getenv('TOURINGCAR_CONTACT_NAME'));
$twig->addGlobal('TOURBUZZ_ORGANISATION', getenv('TOURBUZZ_ORGANISATION'));
$twig->addGlobal('MAPBOX_ACCESS_TOKEN', getenv('MAPBOX_ACCESS_TOKEN'));
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

require_once "../app/bootstrap.php"; // Bootstrap
require_once "routes.php"; // System routes
require_once "../app/routes/index.php"; // Project routes

/**
 * Run!
 */
$app->run();

