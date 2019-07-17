<?php

require "BerichtenServiceRoutes.php";
require "DashboardRoutes.php";
require "HaltesParkerenRoutes.php";
require "PoiRoutes.php";
require "SmsAlertsRoutes.php";
require "UtilRoutes.php";
require "WachtwoordVergetenRoutes.php";


/**
 * Home redirects to current date.
 */
$app->get('/', function () use ($app, $apiRoot) {
    $app->redirect(date('/Y/m/d'));
});


/**
 * Overview of messages for a single day.
 */
$app->get('/:y/:m/:d', function ($y, $m, $d) use ($app, $analytics, $image_api) {

    list($d) = explode('?', $d);

    $status = ''; // if api call fails, set message
    $apiResponse = $app->api->get("berichten/{$y}/{$m}/{$d}");

    if ($apiResponse->statusCode == 200) {
        $berichten = array_filter($apiResponse->body['messages'], function ($bericht) {
            return !empty($bericht['is_live']);
        });
    } else {
        $berichten = [];
        $status = 'UNAVAILABLE';
    }

    usort($berichten, function ($b1, $b2) {
        return $b1['important'] < $b2['important'];
    });

    $N = date('N', strtotime("{$y}-{$m}-{$d}"));
    $j = date('j', strtotime("{$y}-{$m}-{$d}"));

    $day = array(
        'maandag', 'dinsdag', 'woensdag', 'donderdag', 'vrijdag', 'zaterdag', 'zondag'
    );

    $dag = translate($day[(int)$N - 1]);

    // Amsterdam Center Point.
    $center = [
        "lat" => 52.372981,
        "lng" => 4.901327,
    ];

    $mapOptions = [
        "width" => 420,
        "height" => 350,
        "zoom" => 12, // Google Maps zoom level.
        "scale" => 2, // Double resolution for retina display.
        "center" => $center,
    ];

    $berichten = locationItemsToMap($berichten, $mapOptions, false);

    $data = [
        "activetab" => "berichten",
        "lang" => $_SESSION['lang'],
        "berichten" => $berichten,
        "status" => $status,
        "datestring" => "{$y}-{$m}-{$d}",
        "dag" => $dag,
        "j" => $j,
        "d" => $d,
        "m" => $m,
        "Y" => $y,
        "image_api" => $image_api,
        "map" => $mapOptions,
        "adamlogo" => true,
        "analytics" => $analytics,
        "date_picker" => [],
        "layers_legend" => getData('layer_list.json'),
        "infopanel_url" => "/partial/message-overview/{$y}/{$m}/{$d}",
        "activatelayers" => "berichten",
        "center_lat" => 52.372981,
        "center_lon" => 4.901327,
        "zoom" => 16,
        "apikey" => getenv('GOOGLEMAPS_API_KEY'),
        "template" => "web/tourbuzz-map.twig"
    ];

    render($data['template'], $data);
});


/**
 * Details slider of messages for a single day.
 */
$app->get('/:y/:m/:d/details', function ($y, $m, $d) use ($app, $analytics, $image_api) {

    $apiResponse = $app->api->get("berichten/{$y}/{$m}/{$d}");

    $berichten = array_filter($apiResponse->body['messages'], function ($bericht) {
        return !empty($bericht['is_live']);
    });

    usort($berichten, function ($b1, $b2) {
        return $b1['important'] < $b2['important'];
    });

    $N = date('N', strtotime("{$y}-{$m}-{$d}"));

    $day = array(
        'maandag', 'dinsdag', 'woensdag', 'donderdag', 'vrijdag', 'zaterdag', 'zondag'
    );

    $dag = translate($day[(int)$N - 1]);

    $mapOptions = [];

    foreach ($berichten as &$bericht) {
        if ( !empty($bericht['location']) ) {
            $center = $bericht['location'];

            $mapOptions = [
                "width" => 420,
                "height" => 350,
                "zoom" => 15, // Googme Maps zoom level.
                "scale" => 2, // Double resolution for retina display.
                "center" => $center,
            ];

            $bericht['map'] = $mapOptions;
            $bericht['rel_loc'] = array(
                'dX' => 50,
                'dY' => 50
            );
        }
    }

    $data = [
        "activetab" => "berichten",
        "lang" => $_SESSION['lang'],
        "berichten" => $berichten,
        "datestring" => "{$y}-{$m}-{$d}",
        "dag" => $dag,
        "j" => date('j'),
        "d" => $d,
        "m" => $m,
        "Y" => $y,
        "image_api" => $image_api,
        "map" => $mapOptions,
        "analytics" => $analytics,
        "apikey" => getenv('GOOGLEMAPS_API_KEY'),
        "template" => "details.twig",
    ];

    render($data['template'], $data);
});


/**
 * Single message (bericht).
 */
$app->get('/bericht/:id', function ($id) use ($app, $analytics) {

    $apiResponse = $app->api->get("berichten/{$id}");
    $bericht = $apiResponse->body;

    if ($bericht['is_live']) {
        $data = [
            "lang" => "nl",
            "bericht" => $bericht,
            "analytics" => $analytics,
            "template" => "web/partials/message-detail.twig",
        ];
    } else {
        $app->flashNow('error', 'Dit bericht is niet gepubliceerd');
        $data = [
            "template" => "bericht-not-live.twig",
        ];
    }

    render($data["template"], $data);
});

$app->get('/partial/message-overview/:y/:m/:d', function ($y, $m, $d) use ($app, $analytics, $image_api) {

    $apiResponse = $app->api->get("berichten/{$y}/{$m}/{$d}");

    $berichten = array_filter($apiResponse->body['messages'], function ($bericht) {
        return !empty($bericht['is_live']);
    });

    usort($berichten, function ($b1, $b2) {
        return $b1['important'] < $b2['important'];
    });

    $loopIndex = 1;
    foreach ($berichten as &$bericht) {
        $bericht['sort_order'] = $loopIndex;
        $loopIndex++;
    }

    $data = [
        "berichten" => $berichten,
        "lang" => $_SESSION['lang'],
        "d" => $d,
        "m" => $m,
        "Y" => $y,
        "template" => "web/partials/message-overview.twig"
    ];

    render($data['template'], $data);

});

$app->get('/json/message-overview/:y/:m/:d', function ($y, $m, $d) use ($app, $analytics, $image_api) {

    $apiResponse = $app->api->get("berichten/{$y}/{$m}/{$d}");

    $berichten = array_filter($apiResponse->body['messages'], function ($bericht) {
        return !empty($bericht['is_live']);
    });

    usort($berichten, function ($b1, $b2) {
        return $b1['important'] < $b2['important'];
    });

    $loopIndex = 1;
    foreach ($berichten as &$bericht) {
        $bericht['sort_order'] = $loopIndex;
        $loopIndex++;
    }

    $output = [];
    $output['berichten'] = $berichten;
    header('Content-Type: application/json');
    die(json_encode($output));

});

$app->get('/partial/message-detail/:y/:m/:d/bericht/:id', function ($y, $m, $d, $id) use ($app, $analytics, $image_api) {

    $apiResponse = $app->api->get("berichten/{$y}/{$m}/{$d}");

    $berichten = array_filter($apiResponse->body['messages'], function ($bericht) {
        return !empty($bericht['is_live']);
    });

    usort($berichten, function ($b1, $b2) {
        return $b1['important'] < $b2['important'];
    });

    $loopIndex = 1;
    foreach ($berichten as &$bericht) {
        if ($bericht['id'] == $id) {
            $bericht['current_message'] = true;
        }
        $bericht['sort_order'] = $loopIndex;
        $loopIndex++;
    }

    $data = [
        "berichten" => $berichten,
        "lang" => $_SESSION['lang'],
        "d" => $d,
        "m" => $m,
        "Y" => $y,
        "template" => "web/partials/message-detail.twig"
    ];

    render($data['template'], $data);

});

/**
 * Overview of messages for a single day.
 */
$app->get('/message-overview/:y/:m/:d', function ($y, $m, $d) use ($app, $analytics, $image_api) {

    list($d) = explode('?', $d);

    $status = ''; // if api call fails, set message
    $apiResponse = $app->api->get("berichten/{$y}/{$m}/{$d}");

    if ($apiResponse->statusCode == 200) {
        $berichten = array_filter($apiResponse->body['messages'], function ($bericht) {
            return !empty($bericht['is_live']);
        });
    } else {
        $berichten = [];
        $status = 'UNAVAILABLE';
    }

    usort($berichten, function ($b1, $b2) {
        return $b1['important'] < $b2['important'];
    });

    $N = date('N', strtotime("{$y}-{$m}-{$d}"));
    $j = date('j', strtotime("{$y}-{$m}-{$d}"));

    $day = array(
        'maandag', 'dinsdag', 'woensdag', 'donderdag', 'vrijdag', 'zaterdag', 'zondag'
    );

    $dag = translate($day[(int)$N - 1]);

    // Amsterdam Center Point.
    $center = [
        "lat" => 52.372981,
        "lng" => 4.901327,
    ];

    $mapOptions = [
        "width" => 420,
        "height" => 350,
        "zoom" => 12, // Google Maps zoom level.
        "scale" => 2, // Double resolution for retina display.
        "center" => $center,
    ];

    $berichten = locationItemsToMap($berichten, $mapOptions, false);

    $data = [
        "activetab" => "berichten",
        "lang" => $_SESSION['lang'],
        "berichten" => $berichten,
        "status" => $status,
        "datestring" => "{$y}-{$m}-{$d}",
        "dag" => $dag,
        "j" => $j,
        "d" => $d,
        "m" => $m,
        "Y" => $y,
        "image_api" => $image_api,
        "map" => $mapOptions,
        "adamlogo" => true,
        "analytics" => $analytics,
        "date_picker" => [],
        "layers_legend" => getData('layer_list.json'),
        "infopanel" => "message-overview",
        "apikey" => getenv('GOOGLEMAPS_API_KEY'),
        "template" => "web/partials/message-overview.twig"
    ];

    render($data['template'], $data);
});

/**
 * Widget
 */
$app->get('/widget', function () use ($app) {

    $y = date('Y');
    $m = date('m');
    $d = date('d');

    $apiResponse = $app->api->get("berichten/{$y}/{$m}/{$d}");

    $berichten = array_filter($apiResponse->body['messages'], function ($bericht) {
        return !empty($bericht['is_live']);
    });

    usort($berichten, function ($b1, $b2) {
        return $b1['important'] < $b2['important'];
    });

    $N = date('N', strtotime("{$y}-{$m}-{$d}"));
    $j = date('j', strtotime("{$y}-{$m}-{$d}"));

    $day = array(
        'maandag', 'dinsdag', 'woensdag', 'donderdag', 'vrijdag', 'zaterdag', 'zondag'
    );

    $dag = translate($day[(int)$N - 1]);

    $data = [
        "lang" => $_SESSION['lang'],
        "berichten" => $berichten,
        "datestring" => "{$y}-{$m}-{$d}",
        "dag" => $dag,
        "j" => $j,
        "d" => $d,
        "m" => $m,
        "Y" => $y,
        "template" => "widget.twig"
    ];

    render($data['template'], $data);
});


/**
 * RSS
 */
$app->get('/rss', function () use ($app) {

    list($Y, $m, $d) = explode("-", date("Y-m-d"));
    $dateurlstring = "{$Y}/{$m}/{$d}";

    $apiResponse = $app->api->get("berichten/{$dateurlstring}");

    $berichten = array_filter($apiResponse->body['messages'], function ($bericht) {
        return !empty($bericht['is_live']);
    });

    usort($berichten, function ($b1, $b2) {
        return $b1['important'] < $b2['important'];
    });

    $data = [
        "berichten" => $berichten,
        "dateurlstring" => $dateurlstring,
        "datetime" => date(DATE_ISO8601),
        "pubDate" => date(DATE_ISO8601, strtotime(date("Y-m-d"))),
        "dateCode" => date("Ymd"),
    ];

    render("rss.twig", $data, ["Content-type" => "application/xml"]);
});


/**
 * Offline version
 */
$app->get('/offline', function () use ($app) {

    list($Y, $m, $d) = explode("-", date("Y-m-d"));
    $dateurlstring = "{$Y}/{$m}/{$d}";

    $apiResponse = $app->api->get("berichten/{$dateurlstring}");

    $berichten = array_filter($apiResponse->body['messages'], function ($bericht) {
        return !empty($bericht['is_live']);
    });

    usort($berichten, function ($b1, $b2) {
        return $b1['important'] < $b2['important'];
    });

    $apiResponse = $app->api->get("haltes");
    $haltes = $apiResponse->body['haltes'];

    $apiResponse = $app->api->get("parkeerplaatsen");
    $parkeerplaatsen = $apiResponse->body['parkeerplaatsen'];


    $data = [
        "berichten"       => $berichten,
        "haltes"          => $haltes,
        "parkeerplaatsen" => $parkeerplaatsen,
        "lang"            => $_SESSION['lang'],
        "template"        => "offline.twig",
    ];

    render($data["template"], $data);
});