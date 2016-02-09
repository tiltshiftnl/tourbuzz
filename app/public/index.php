<?php

ini_set("error_reporting", E_ALL);
ini_set("display_errors", 1);

require "../vendor/autoload.php";

/**
 * Twig plugin inladen.
 */
$app = new \Slim\Slim(["view" => new \Slim\Views\Twig()]);
$view = $app->view();
$view->setTemplatesDirectory("../app/views");
$view->parserExtensions = [new \Slim\Views\TwigExtension()];

function translate($msg) {
    return 'translate('.$msg.')';
}
$twig = $app->view()->getEnvironment();
$twig->addFunction('__', new Twig_Function_Function('translate'));

require_once "routes.php"; // System routes

require_once "../app/routes.php"; // Project routes

/**
 * Run!
 */
$app->run();


