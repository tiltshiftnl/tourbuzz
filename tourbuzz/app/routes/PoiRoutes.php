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

/**
 * gpx
 */
$app->get('/gpx', function () use ($app) {

    $dom = new \DOMDocument('1.0', 'utf-8');

    $gpxRoot = $dom->createElement('gpx');
    $gpxRoot->setAttribute('version', '1.0');
    $gpxRoot->setAttribute('creator', 'Tourbuzz.nl, Gemeente Amsterdam');
    $gpxRoot->setAttribute('xmlns', 'http://www.topografix.com/GPX/1/0');
    $gpxRoot->setAttribute('xmlns:xsi', 'http://www.w3.org/2001/XMLSchema-instance');
    $gpxRoot->setAttribute('xsi:schemaLocation', 'http://www.topografix.com/GPX/1/0 http://www.topografix.com/GPX/1/0/gpx.xsd');
    $dom->appendChild($gpxRoot);

    $name = $dom->createElement('name');
    $name->appendChild($dom->createTextNode('Gemeente Amsterdam - Tourbuzz - Haltes'));
    $gpxRoot->appendChild($name);

    /**
     * @var ApiResponse $apiResponse;
     */
    $apiResponse = $app->api->get('haltes');
    $rs = $apiResponse->body;
    foreach ($rs['haltes'] as $halte) {
        $wpt = $dom->createElement('wpt');
        $wpt->setAttribute('lat', $halte['location']['lat']);
        $wpt->setAttribute('lon', $halte['location']['lng']);
        $gpxRoot->appendChild($wpt);

        $name = $dom->createElement('name');
        $name->appendChild($dom->createTextNode($halte['haltenummer']));
        $wpt->appendChild($name);

        $url= $dom->createElement('url');
        $url->appendChild($dom->createTextNode('https://' . $app->request->getHost() . $app->urlFor('halte', ['slug' => $halte['haltenummer']])));
        $wpt->appendChild($url);

        $cmt = $dom->createElement('cmt');
        $cmt->appendChild($dom->createTextNode('NL: ' . $halte['haltenummer'] . ' ' . translate('Alleen passagiers in- en uit laten stappen. Let op: max 10 min.', 'nl') . "\r\n "));
        $cmt->appendChild($dom->createTextNode('EN: ' . $halte['haltenummer'] . ' ' . translate('Alleen passagiers in- en uit laten stappen. Let op: max 10 min.', 'en') . "\r\n "));
        $cmt->appendChild($dom->createTextNode('DE: ' . $halte['haltenummer'] . ' ' . translate('Alleen passagiers in- en uit laten stappen. Let op: max 10 min.', 'de') . "\r\n "));
        $cmt->appendChild($dom->createTextNode('FR: ' . $halte['haltenummer'] . ' ' . translate('Alleen passagiers in- en uit laten stappen. Let op: max 10 min.', 'fr') . "\r\n "));
        $cmt->appendChild($dom->createTextNode('ES: ' . $halte['haltenummer'] . ' ' . translate('Alleen passagiers in- en uit laten stappen. Let op: max 10 min.', 'es') ));
        $wpt->appendChild($cmt);
    }

    /**
     * @var ApiResponse $apiResponse;
     */
    $apiResponse = $app->api->get('parkeerplaatsen');
    $rs = $apiResponse->body;
    foreach ($rs['parkeerplaatsen'] as $parkeerplaats) {
        if (!$parkeerplaats['naam']) $parkeerplaats['naam'] = $parkeerplaats['nummer'];
        $wpt = $dom->createElement('wpt');
        $wpt->setAttribute('lat', $parkeerplaats['location']['lat']);
        $wpt->setAttribute('lon', $parkeerplaats['location']['lng']);
        $gpxRoot->appendChild($wpt);

        $name = $dom->createElement('name');
        $name->appendChild($dom->createTextNode($parkeerplaats['naam']));
        $wpt->appendChild($name);

        $url= $dom->createElement('url');
        $url->appendChild($dom->createTextNode('https://' . $app->request->getHost() . $app->urlFor('parkeerplaats', ['slug' => $parkeerplaats['nummer']])));
        $wpt->appendChild($url);

        $cmt = $dom->createElement('cmt');
        $cmt->appendChild($dom->createTextNode('NL: ' . $parkeerplaats['naam'] . ' ' . translate('Op deze locaties kunt u uw bus parkeren. Klik hier voor de tarieven.', 'nl') . "\r\n "));
        $cmt->appendChild($dom->createTextNode('EN: ' . $parkeerplaats['naam'] . ' ' . translate('Op deze locaties kunt u uw bus parkeren. Klik hier voor de tarieven.', 'en') . "\r\n "));
        $cmt->appendChild($dom->createTextNode('DE: ' . $parkeerplaats['naam'] . ' ' . translate('Op deze locaties kunt u uw bus parkeren. Klik hier voor de tarieven.', 'de') . "\r\n "));
        $cmt->appendChild($dom->createTextNode('FR: ' . $parkeerplaats['naam'] . ' ' . translate('Op deze locaties kunt u uw bus parkeren. Klik hier voor de tarieven.', 'fr') . "\r\n "));
        $cmt->appendChild($dom->createTextNode('ES: ' . $parkeerplaats['naam'] . ' ' . translate('Op deze locaties kunt u uw bus parkeren. Klik hier voor de tarieven.', 'es') ));
        $wpt->appendChild($cmt);
    }

    header("Content-type: text/xml");
    header("Content-Disposition: attachment; filename=touringcar.gpx");
    header("Pragma: no-cache");
    header("Expires: 0");
    echo $dom->saveXML();
});