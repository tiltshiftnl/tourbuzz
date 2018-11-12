<?php

require_once(__DIR__ . "/config/config.php");

$localConfigFilePath = __DIR__ . "/config/config_local.php";

if (file_exists($localConfigFilePath)) {
	require_once($localConfigFilePath);
}

require_once(__DIR__ . "/ApiClient.php");

$app->container->singleton('api', function () use ($apiRoot) {
    return new ApiClient($apiRoot);
});

// Environment variables loading
$dotenv = new Dotenv\Dotenv(__DIR__.'/../');
$dotenv->load();

/**
 * Adds relative positions to items based on Google Maps zoom-level.
 * Needed to plot markers on static map using css.
 */
function locationItemsToMap($items, $mapOptions, $filter = true) {
    // Pixels per dLat and dLng for Amsterdam (approx) at zoomlevels.
    $ppd = [
        "lat" => [
            "12" => 4770.821985494679,
            "13" => 9541.643998239988,
            "14" => 19083.28801010367,
            "15" => 38166.5760271421,
            "16" => 76333.15205759634,
            "17" => 152666.3041185048,
        ],
        "lng" => [
            "12" => 2912.711111111111,
            "13" => 5825.422222222222,
            "14" => 11650.844444444445,
            "15" => 23301.68888888889,
            "16" => 46603.37777777778,
            "17" => 93206.75555555556,
        ],
    ];

    // Calculate relative positions on the map.
    $items = array_map(function ($item) use ($mapOptions, $ppd) {
        if (!empty($item['location'])) {
            $dLat = $item['location']['lat'] - $mapOptions['center']['lat'];
            $dLng = $item['location']['lng'] - $mapOptions['center']['lng'];
            $dY = $dLat * 100 * $ppd['lat'][$mapOptions['zoom']];
            $dX = $dLng * 100 * $ppd['lng'][$mapOptions['zoom']];
            $item['rel_loc'] = [
                "dX" => 50 + ($dX / ($mapOptions['width'])),
                "dY" => 50 - ($dY / ($mapOptions['height'])),
            ];
        }
        return $item;
    }, $items);

    // Remove rel_loc for points outside of the map.
    if ($filter) {
        $items = array_map(function ($item) {
            if (empty($item['rel_loc'])) {
                return $item;
            }
            if (($item['rel_loc']['dX'] > 100) ||
                ($item['rel_loc']['dX'] < 0) ||
                ($item['rel_loc']['dY'] > 100) ||
                ($item['rel_loc']['dY'] < 0)) {
                unset($item['rel_loc']);
            }
            return $item;
        }, $items);
    }

    return $items;
}


/**
 * Before routing.
 */
$app->hook('slim.before', function() use ($app) {
    if (empty($_SESSION['lang'])) {
        $_SESSION['lang'] = 'nl';
    }
    $lang = $app->request()->params('lang');
    if (isset($lang) && in_array($lang, array('nl','de','en','es'))) {
        $_SESSION['lang'] = $lang;
    }
});
