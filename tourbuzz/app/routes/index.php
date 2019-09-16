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

    $loopIndex = 1;
    foreach ($berichten as &$bericht) {
        $bericht['sort_order'] = $loopIndex;
        $loopIndex++;
    }

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

    //$berichten = locationItemsToMap($berichten, $mapOptions, false);

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
        "adamlogo" => true,
        "analytics" => $analytics,
        "date_picker" => [],
        "layers_legend" => getData('layer_list.json'),
        "infopanel_url" => "/{$y}/{$m}/{$d}?partial=panel",
        "activatelayers" => "berichten",
        "center_lat" => 52.372981,
        "center_lng" => 4.901327,
        "zoom" => 16,
        "template" => "web/tourbuzz-map.twig"
    ];

    if (isset($_REQUEST['partial'])) {
        $data["template"] = "web/partials/message-overview.twig";
    }

    render($data['template'], $data);
});


/**
 * Single message (bericht).
 */
$app->get('/bericht/:id(/:Y/:m/:d)', function ($id, $Y = NULL, $m = NULL, $d = NULL) use ($app, $analytics, $image_api) {

    $apiResponse = $app->api->get("berichten/{$id}");
    $bericht = $apiResponse->body;

    if ($bericht['is_live']) {

        // Use startdate of message to initialise messages on map
        if (empty($Y)) {
            $datestring = $bericht['startdate'];
            $dateParts = explode("-", $datestring);
            $Y = $dateParts[0];
            $m = $dateParts[1];
            $d = $dateParts[2];
        } else {
            $datestring = $Y."-".$m."-".$d;
        }

        // Determine the id corresponding to the messages on that day
        $apiResponse = $app->api->get("berichten/{$Y}/{$m}/{$d}");

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

        $sortOrder = 1;
        $loopIndex = 1;
        foreach ($berichten as $item) {
            if ($item['id'] == $bericht['id']) {
                $sortOrder = $loopIndex;
            }
            $loopIndex++;
        }

        $data = [
            "lang" => "nl",
            "bericht" => $bericht,
            "sort_order" => $sortOrder,
            "analytics" => $analytics,
            "datestring" => $datestring,
            //"j" => date('j'),
            "d" => $d,
            "m" => $m,
            "Y" => $Y,
            "center_lat" => 52.372981,
            "center_lng" => 4.901327,
            "zoom" => 16,
            "date_picker" => [],
            "image_api" => $image_api,
            "layers_legend" => getData('layer_list.json'),
            "infopanel_url" => "/bericht/{$id}/{$Y}/{$m}/{$d}?partial=panel",
            "activatelayers" => "berichten",
            "template" => "web/tourbuzz-map.twig",
        ];

        if (isset($_REQUEST['partial'])) {
            $data["template"] = "web/partials/message-detail.twig";
        }

    } else {
        $app->flashNow('error', 'Dit bericht is niet gepubliceerd');
        $data = [
            "template" => "bericht-not-live.twig",
        ];
    }

    render($data["template"], $data);
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


$app->get('/widget', function () use ($app, $analytics, $image_api) {

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

    $loopIndex = 1;
    foreach ($berichten as &$bericht) {
        $bericht['sort_order'] = $loopIndex;
        $loopIndex++;
    }

    $data = [
        "berichten" => $berichten,
        "lang" => $_SESSION['lang'],
        "image_api" => $image_api,
        "adamlogo" => true,
        "analytics" => $analytics,
        "date_picker" => [],
        "template" => "web/message-widget.twig"
    ];

    $app->response->headers->set('X-Frame-Options', 'allow-from https://amsterdam.nl/');
    $app->response->headers->set('Content-Security-Policy', "frame-ancestors 'self' https://*.amsterdam.nl/ https://*.bma-collective.com/");
    render($data['template'], $data);
});


$app->get('/embed/map', function () use ($app, $analytics, $image_api) {

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

    $loopIndex = 1;
    foreach ($berichten as &$bericht) {
        $bericht['sort_order'] = $loopIndex;
        $loopIndex++;
    }

    $data = [
        "berichten" => $berichten,
        "lang" => $_SESSION['lang'],
        "image_api" => $image_api,
        "adamlogo" => true,
        "analytics" => $analytics,
        "date_picker" => [],
        "layers_legend" => getData('layer_list.json'),
        "infopanel_url" => "/{$y}/{$m}/{$d}?partial=panel",
        "activatelayers" => "berichten",
        "d" => $d,
        "m" => $m,
        "Y" => $y,
        "embedded" => 1,
        "center_lat" => 52.372981,
        "center_lng" => 4.901327,
        "zoom" => 16,
        "template" => "web/embed-map.twig"
    ];

    $app->response->headers->set('X-Frame-Options', 'allow-from https://amsterdam.nl/');
    $app->response->headers->set('Content-Security-Policy', "frame-ancestors 'self' https://*.amsterdam.nl/ https://*.bma-collective.com/");
    render($data['template'], $data);
});

/**
 * Overview of routes for coaches
 */
$app->get('/routes', function () use ($app, $analytics, $image_api) {

    $data = [
        "activetab" => "routes",
        "lang" => $_SESSION['lang'],
        "image_api" => $image_api,
        "adamlogo" => true,
        "analytics" => $analytics,
        "date_picker" => [],
        "layers_legend" => getData('layer_list.json'),
        "infopanel_url" => "/routes?partial=panel",
        "activatelayers" => "doorrijhoogtes,aanbevolenroutes,verplichteroutes,bestemmingsverkeer",
        "panel_reverse_order" => true,
        "template" => "web/tourbuzz-map.twig"
    ];

    if (isset($_REQUEST['partial'])) {
        $data["template"] = "web/partials/routes-overview.twig";
    }

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
 * Health check
 */
$app->get('/status/health', function () use ($app) {
    header("HTTP/1.1 200 OK");
    die("Works!");
});

/**
 * Health check - alternative
 */
$app->get('/haltes', function () use ($app) {
    header("HTTP/1.1 200 OK");
    die("Works!");
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