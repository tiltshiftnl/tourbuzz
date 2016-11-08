<?php

/*******
 * POI *
 *******/


/**
 * Overview of downloadable formats for GPS navigation.
 */
$app->get('/downloads', function () use ($app, $analytics) {

    $data = [
        "analytics" => $analytics,
        "template" => "downloads.twig",
    ];

    render($data["template"], $data);
});


/**
 * poi
 */
$app->get('/poi', function () use ($app) {
    header("Content-type: text/csv");
    header("Content-Disposition: attachment; filename=touringcar.csv");
    header("Pragma: no-cache");
    header("Expires: 0");

    $out = fopen('php://output', 'w');

    /**
     * @var ApiResponse $apiResponse;
     */
    $apiResponse = $app->api->get('haltes');
    $rs = $apiResponse->body;
    foreach ($rs['haltes'] as $halte) {
        fputcsv($out, [
            $halte['location']['lng'],
            $halte['location']['lat'],
            $halte['haltenummer'],
            'halte',
        ]);
    }

    /**
     * @var ApiResponse $apiResponse;
     */
    $apiResponse = $app->api->get('parkeerplaatsen');
    $rs = $apiResponse->body;
    foreach ($rs['parkeerplaatsen'] as $parkeerplaats) {
        if (!$parkeerplaats['naam']) $parkeerplaats['naam'] = $parkeerplaats['nummer'];
        fputcsv($out, [
            $parkeerplaats['location']['lng'],
            $parkeerplaats['location']['lat'],
            $parkeerplaats['naam'],
            'parkeerplaats',
        ]);
    }
});


/**
 * ov2
 */
$app->get('/ov2', function () use ($app) {
    $poiFile = new \Tourbuzz\Format\Ov2File();

    /**
     * @var ApiResponse $apiResponse;
     */
    $apiResponse = $app->api->get('haltes');
    $rs = $apiResponse->body;
    foreach ($rs['haltes'] as $halte) {
        $poiFile->add_POI($halte['location']['lat'], $halte['location']['lng'], $halte['haltenummer']);
    }

    /**
     * @var ApiResponse $apiResponse;
     */
    $apiResponse = $app->api->get('parkeerplaatsen');
    $rs = $apiResponse->body;
    foreach ($rs['parkeerplaatsen'] as $parkeerplaats) {
        if (!$parkeerplaats['naam']) $parkeerplaats['naam'] = $parkeerplaats['nummer'];
        $poiFile->add_POI($parkeerplaats['location']['lat'], $parkeerplaats['location']['lng'], $parkeerplaats['naam']);
    }

    header("Expires: Mon, 26 Jul 1997 05:00:00 GMT"); // some day in the past
    header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
    header("Content-type: application/x-download");
    header("Content-Disposition: attachment; filename=touringcars.ov2");
    header("Content-Transfer-Encoding: binary");
    echo $poiFile->content;
});