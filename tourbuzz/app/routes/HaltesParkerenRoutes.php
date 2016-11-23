<?php

/*****************************
 * Haltes en parkeerplaatsen *
 *****************************/


/**
 * Haltes en parkeerplaatsen
 */
$app->get('/haltes-parkeerplaatsen', function () use ($app, $analytics) {

    $apiResponse = $app->api->get("haltes");
    $haltes = $apiResponse->body['haltes'];

    $apiResponse = $app->api->get("parkeerplaatsen");
    $parkeerplaatsen = $apiResponse->body['parkeerplaatsen'];

    // Amsterdam Center Point.
    $center = [
        "lat" => 52.372981,
        "lng" => 4.901327,
    ];

    $mapOptions = [
        "width" => 420,
        "height" => 350,
        "zoom" => 14, // Google Maps zoom level.
        "scale" => 2, // Double resolution for retina display.
        "center" => $center,
    ];

    //$haltes = locationItemsToMap($haltes, $mapOptions, true);

    $data = [
        "m" => date('m'),
        "d" => date('d'),
        "Y" => date('Y'),
        "lang" => $_SESSION['lang'],
        "activetab" => "haltes-parkeerplaatsen",
        "haltes" => $haltes,
        "parkeerplaatsen" => $parkeerplaatsen,
        "map" => $mapOptions,
        "analytics" => $analytics,
        "apikey" => "AIzaSyDbdT24XheIFcsXjZhNRI9KMG806-feOr4",
        "template" => "haltes-parkeerplaatsen.twig",
    ];

    render($data['template'], $data);
});


/**
 * Single busstop (halte).
 */
$app->get('/haltes/:slug', function ($slug) use ($app, $analytics) {

    $apiResponse = $app->api->get("haltes");
    $haltes = $apiResponse->body['haltes'];
    $halte = $haltes[$slug];

    $center = $halte['location'];

    $mapOptions = [
        "width" => 420,
        "height" => 350,
        "zoom" => 16,
        "scale" => 2,
        "center" => $center,
    ];

    $haltes = locationItemsToMap($haltes, $mapOptions);

    $data = [
        "m" => date('m'),
        "d" => date('d'),
        "Y" => date('Y'),
        "lang" => $_SESSION['lang'],
        "activetab" => "haltes-parkeerplaatsen",
        "record" => $halte,
        "haltes" => $haltes,
        "map" => $mapOptions,
        "analytics" => $analytics,
        "template" => "halte.twig",
    ];

    render($data['template'], $data);
});


/**
 * Single busparking (parkeerplaats).
 */
$app->get('/parkeerplaatsen/:slug', function ($slug) use ($app, $analytics) {

    $apiResponse = $app->api->get("parkeerplaatsen");
    $parkeerplaatsen = $apiResponse->body['parkeerplaatsen'];
    $parkeerplaats = $parkeerplaatsen[$slug];

    $center = $parkeerplaats['location'];

    $mapOptions = [
        "width" => 420,
        "height" => 350,
        "zoom" => 15,
        "scale" => 2,
        "center" => $center,
    ];

    $parkeerplaatsen = locationItemsToMap($parkeerplaatsen, $mapOptions);

    $data = [
        "m" => date('m'),
        "d" => date('d'),
        "Y" => date('Y'),
        "lang" => $_SESSION['lang'],
        "activetab" => "haltes-parkeerplaatsen",
        "record" => $parkeerplaats,
        "parkeerplaatsen" => $parkeerplaatsen,
        "map" => $mapOptions,
        "analytics" => $analytics,
        "template" => "parkeerplaats.twig",
    ];

    render($data['template'], $data);
});


/**
 * Mockup halteprofiel.
 */
$app->get('/halteprofiel/:slug', function ($slug) use ($app, $analytics) {

    $apiResponse = $app->api->get("haltes");
    $haltes = $apiResponse->body['haltes'];
    $halte = $haltes[$slug];

    $center = $halte['location'];

    $mapOptions = [
        "width" => 420,
        "height" => 350,
        "zoom" => 16,
        "scale" => 2,
        "center" => $center,
    ];

    $haltes = locationItemsToMap($haltes, $mapOptions);

    $data = [
        "m" => date('m'),
        "d" => date('d'),
        "Y" => date('Y'),
        "lang" => $_SESSION['lang'],
        "activetab" => "haltes-parkeerplaatsen",
        "record" => $halte,
        "haltes" => $haltes,
        "map" => $mapOptions,
        "analytics" => $analytics,
        "template" => "halte-profiel.twig",
    ];

    render($data['template'], $data);
});


/**
 * Single busparking (parkeerplaats).
 */
$app->get('/parkeerplaatsprofiel/:slug', function ($slug) use ($app, $analytics) {

    $apiResponse = $app->api->get("parkeerplaatsen");
    $parkeerplaatsen = $apiResponse->body['parkeerplaatsen'];
    $parkeerplaats = $parkeerplaatsen[$slug];

    $center = $parkeerplaats['location'];

    $mapOptions = [
        "width" => 420,
        "height" => 350,
        "zoom" => 15,
        "scale" => 2,
        "center" => $center,
    ];

    $parkeerplaatsen = locationItemsToMap($parkeerplaatsen, $mapOptions);

    // Vialis data
    //$beschikbaar = file_get_contents('http://opd.it-t.nl/data/amsterdam/ParkingLocation.json');
    //$beschikbaar = json_decode($beschikbaar, true);

    $data = [
        "m" => date('m'),
        "d" => date('d'),
        "Y" => date('Y'),
        //"beschikbaar" => $beschikbaar['features'][1]['properties'],
        "lang" => $_SESSION['lang'],
        "activetab" => "haltes-parkeerplaatsen",
        "record" => $parkeerplaats,
        "parkeerplaatsen" => $parkeerplaatsen,
        "map" => $mapOptions,
        "analytics" => $analytics,
        "template" => "parkeerplaats-profiel.twig",
    ];

    render($data['template'], $data);
});
