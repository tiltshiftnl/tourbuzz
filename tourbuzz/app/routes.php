<?php

require_once("config/config.php");
$localConfigFilePath = __DIR__ . "/config/config_local.php";
if (file_exists($localConfigFilePath)) {
	require_once($localConfigFilePath);
}

require_once("ApiClient.php");

$app->container->singleton('api', function () use ($apiRoot) {
  return new ApiClient($apiRoot);
});

require_once("distance.php");

/**
 *
 */
function locationItemsToMap($items, $mapOptions) {
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
        $dLat = $item['location']['lat'] - $mapOptions['center']['lat'];
        $dLng = $item['location']['lng'] - $mapOptions['center']['lng'];
        $dY = $dLat * 100 * $ppd['lat'][$mapOptions['zoom']];
        $dX = $dLng * 100 * $ppd['lng'][$mapOptions['zoom']];
        $item['rel_loc'] = [
            "dX" => 50 + ($dX / ($mapOptions['width'])),
            "dY" => 50 - ($dY / ($mapOptions['height'])),
        ];
        return $item;
    }, $items);

    // Filter out points outside of the map.
    $items = array_filter($items, function ($item) {
        return
            !($item['rel_loc']['dX'] > 100) &&
            !($item['rel_loc']['dX'] < 0) &&
            !($item['rel_loc']['dY'] > 100) &&
            !($item['rel_loc']['dY'] < 0);
    });

    return $items;
}

/**
 * Before
 */
$app->hook('slim.before', function() use ($app) {
    if (empty($_SESSION['lang'])) {
        $_SESSION['lang'] = 'nl';
    }
    $lang = $app->request()->params('lang');
    if (isset($lang) && in_array($lang, array('nl', /*'fr',*/ 'en'))) {
        $_SESSION['lang'] = $lang;
    }
});


/**
 * Home
 */
$app->get('/', function () use ($app, $apiRoot) {

    if ( !empty($_SESSION['firstvisit']) ) {
        //$app->redirect(date('/Y/m/d'));
    }

    $_SESSION['firstvisit'] = true;

    $data = [
        "redirect" => date('/Y/m/d'),
        "template" => "splash.twig",
    ];

    render($data['template'], $data);
});


/**
 * Parkeerplaatsen
 */
$app->get('/parkeren', function () use ($app, $apiRoot) {

    $res = $app->api->get("parkeerplaatsen");
    $parkeerplaatsen = $res['parkeerplaatsen'];

    //FIXME Global Amsterdam Center point
    $center = [
        "lat" => 52.372981,
        "lng" => 4.901327,
    ];

    $mapOptions = [
        "width" => 420,
        "height" => 350,
        "zoom" => 12,
        "scale" => 2,
        "center" => $center,
    ];

    $parkeerplaatsen = locationItemsToMap($parkeerplaatsen, $mapOptions);

    $data = [
        "activetab" => "parkeren",
        "parkeerplaatsen" => $parkeerplaatsen,
        "d" => date('d'),
        "m" => date('m'),
        "Y" => date('Y'),
        "center" => $center, //FIXME Use $mapOptions in template and remove this.
        "map" => $mapOptions,
        "template" => "parkeerplaatsen.twig",
    ];

    render($data['template'], $data);
});


/**
 * Halte profiel
 */
$app->get('/haltes/:slug', function ($slug) use ($app, $apiRoot) {

    $res = $app->api->get("haltes");
    $haltes = $res['haltes'];
    $halte = $haltes[$slug];

    $center = $halte['location'];

    $mapOptions = [
        "width" => 420,
        "height" => 350,
        "zoom" => 15,
        "scale" => 2,
        "center" => $center,
    ];

    $haltes = locationItemsToMap($haltes, $mapOptions);

    $data = [
        "activetab" => "haltes",
        "record" => $halte,
        "haltes" => $haltes,
        "map" => $mapOptions,
        "center" => $center, //FIXME Use $mapOptions in template and remove this.
        "template" => "halte.twig",
        "d" => date("d"),
        "m" => date("m"),
        "Y" => date("Y"),
    ];

    render($data['template'], $data);
});

// Comparison function
function cmpdistance($a, $b) {
    if ($a['afstand'] == $b['afstand']) {
        return 0;
    }
    return ($a['afstand'] < $b['afstand']) ? -1 : 1;
}


/**
 * Parkeerplaats profiel
 */
$app->get('/parkeerplaatsen/:slug', function ($slug) use ($app, $apiRoot) {

    $res = $app->api->get("parkeerplaatsen");
    $parkeerplaatsen = $res['parkeerplaatsen'];
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
        "activetab" => "parkeren",
        "record" => $parkeerplaats,
        "parkeerplaatsen" => $parkeerplaatsen,
        "map" => $mapOptions,
        "center" => $center, //FIXME Use $mapOptions in template and remove this.
        "template" => "parkeerplaats.twig",
        "d" => date("d"),
        "m" => date("m"),
        "Y" => date("Y"),
    ];

    render($data['template'], $data);

    /*$parkeerplaats = $app->api->get("parkeerplaatsen/{$slug}");

    $data = [
        "activetab" => "parkeren",
        "record" => $parkeerplaats['parkeerplaats'],
        "template" => "parkeerplaats.twig",
        "d" => date("d"),
        "m" => date("m"),
        "Y" => date("Y"),
    ];

    render($data['template'], $data);*/
});


/*****************
/* Admin Routes
 ****************/

/**
 * Logout
 */
$app->get('/dashboard', function () use ($app) {
    $app->redirect("/dashboard/berichten");
});


/**
 * Login
 */
$app->get('/dashboard/login', function () use ($apiRoot) {

    $data = [
        "template" => "dashboard/login.twig",
    ];

    render($data['template'], $data);
})->name("login");


/**
 * Logout
 */
$app->get('/dashboard/logout', function () use ($app, $apiRoot) {

    $res = $app->api->delete("auth?token={$_SESSION['auth_token']}");
    unset($_SESSION['auth_token']);

    $app->flash('success', 'Je bent nu uitgelogd. Tot kijk!');
    $app->redirect("/dashboard/login");
})->name("logout");


/**
 * Login post
 */
$app->post('/dashboard/login', function () use ($app, $apiRoot) {

    $fields = array(
        'username' => $app->request->post('username'),
        'password' => $app->request->post('password')
    );

    $res = $app->api->post("auth", $fields);
    if (!$res) {
        $app->flash('error', 'Onjuiste inloggegevens');
        $app->redirect("/dashboard/login");
    }

    $_SESSION['auth_token'] = $res['token'];
    $_SESSION['username']   = $app->request->post('username');

    $app->flash('success', 'Je bent ingelogd');
    $app->redirect("/dashboard/berichten");
})->name("login");


/**
 * Berichten get
 */
$app->get('/dashboard/berichten', function () use ($app, $image_api) {

    // Check token
    if ( empty($_SESSION['auth_token']) ) {
        $app->flash('error', 'Eerst inloggen');
        $app->redirect("/dashboard/login");
    }

    $res = $app->api->get('auth?token='.$_SESSION['auth_token']);
    if (!$res) {
        $app->redirect("/dashboard/logout");
    }

    $res = $app->api->get("berichten");

    $data = [
        "berichten" => $res['messages'],
        "image_api" => $image_api,
        "api" => $app->api->getApiRoot(),
        "username" => $_SESSION['username'],
        "template" => "dashboard/berichten.twig",
        "d" => date("d"),
        "m" => date("m"),
        "Y" => date("Y"),
    ];

    render($data['template'], $data);
})->name("berichten");

/**
 * Berichten post
 */
$app->post('/dashboard/berichten', function () use ($app, $image_api) {

    $fields = array(
        'category' => $app->request->post('category'),
      	'title' => $app->request->post('title'),
      	'body' => $app->request->post('body'),
      	'advice' => $app->request->post('advice'),
      	'title_en' => $app->request->post('title_en'),
      	'body_en' => $app->request->post('body_en'),
        'advice_en' => $app->request->post('advice_en'),
      	'title_fr' => $app->request->post('title_fr'),
      	'body_fr' => $app->request->post('body_fr'),
      	'startdate' => $app->request->post('startdate'),
      	'enddate' => $app->request->post('enddate'),
      	'id' => $app->request->post('id'),
        'link' => $app->request->post('link'),
        'image_url' => $app->request->post('image_url'),
        'important' => $app->request->post('important'),
        'is_live' => $app->request->post('is_live'),
    );

    if ( empty ($fields['title']) ) {
        $app->flashNow('error', 'Titel is niet ingevuld');

        $berichten = $app->api->get("berichten");

        $data = [
            "berichten" => $berichten['messages'],
            "bericht" => $fields,
            "image_api" => $image_api,
            "api" => $app->api->getApiRoot(),
            "template" => "dashboard/berichten.twig",
        ];

        render($data['template'], $data);
    } else {

        $token = $_SESSION['auth_token'];

        $app->api->setToken($_SESSION['auth_token']);
        $res = $app->api->post("berichten", $fields);
        if (!$res) {
            $app->flash('error', 'Mag niet! Unauthorized');
            $app->redirect("/dashboard/berichten");
        }

        $app->flash('success', 'Bericht toegevoegd');
        $app->redirect("/dashboard/berichten");
    }
})->name("berichten");


/**
 * Berichten bewerken
 */
$app->get('/dashboard/berichten/:id', function ($id) use ($app, $image_api) {

    $berichten = $app->api->get("berichten");

    $data = [
        "test" => "world",
        "bericht" => $berichten['messages'][$id],
        "berichten" => $berichten['messages'],
        "image_api" => $image_api,
        "api" => $app->api->getApiRoot(),
        "template" => "dashboard/berichten.twig",
    ];

    render($data['template'], $data);
});


/**
 * Berichten verwijderen
 */
$app->post('/dashboard/berichten/verwijderen', function () use ($app) {

    $ids = $app->request->post('ids');
    $token = $_SESSION['auth_token'];

    $app->api->setToken($_SESSION['auth_token']);
    $res = $app->api->delete("berichten", $ids);
    if (!$res) {
        $app->flash('error', 'Mag niet! Unauthorized');
        $app->redirect("/dashboard/berichten");
    }

    $app->flash('success', 'Bericht(en) verwijderd');
    $app->redirect("/dashboard/berichten");
});


/**
 * Styleguide
 */
$app->get('/styleguide', function () {

    $data = [
        "template" => "styleguide.twig",
    ];

    render($data['template'], $data);
});

/**
 * Dag
 */
$app->get('/:y/:m/:d', function ($y, $m, $d) use ($app, $analytics, $image_api) {

    $res = $app->api->get("berichten/{$y}/{$m}/{$d}");

    $berichten = array_filter($res['messages'], function ($bericht) {
        return !empty($bericht['is_live']);
    });

    usort($berichten, function ($b1, $b2) {
        return $b1['important'] < $b2['important'];
    });

    $volgende = "/".str_replace('-', '/', $res['_nextDate']);
    $vorige   = "/".str_replace('-', '/', $res['_prevDate']);

    //$cruisekalender = $app->api->get("cruisekalender/{$y}/{$m}/{$d}");

    $N = date('N', strtotime("{$y}-{$m}-{$d}"));

    $day = array (
        'ma', 'di', 'wo', 'do', 'vr', 'za', 'zo'
    );

    $dag = translate($day[(int)$N - 1]);

    //FIXME Global Amsterdam Center Point
    $center = [
        "lat" => 52.372981,
        "lng" => 4.901327,
    ];

    $mapOptions = [
        "width" => 420,
        "height" => 350,
        "zoom" => 12,
        "scale" => 2,
        "center" => $center,
    ];

    $berichten = locationItemsToMap($berichten, $mapOptions);

    $data = [
        "activetab" => "berichten",
        "lang" => $_SESSION['lang'],
        "berichten" => $berichten,
        "volgende" => $volgende,
        "vorige" => $vorige,
        "datestring" => "{$y}-{$m}-{$d}",
        "dag" => $dag,
        "d" => $d,
        "m" => $m,
        "Y" => $y,
        "api" => $app->api->getApiRoot(),
        "image_api" => $image_api,
        "analytics" => $analytics,
        //"cruisekalender" => $cruisekalender['items'],
        "timestamp" => $res['_timestamp'],
        "center" => $center, //FIXME Use $mapOptions in template and remove this.
        "map" => $mapOptions,
        "template" => "home.twig",
    ];
    render($data['template'], $data);
})->name("home");

/**
 * Haltes
 */
$app->get('/haltes', function () use ($app, $apiRoot) {

    $res = $app->api->get("haltes");
    $haltes = $res['haltes'];

    //FIXME Global Amsterdam Center Point
    $center = [
        "lat" => 52.372981,
        "lng" => 4.901327,
    ];

    $mapOptions = [
        "width" => 420,
        "height" => 350,
        "zoom" => 14,
        "scale" => 2,
        "center" => $center,
    ];

    $haltes = locationItemsToMap($haltes, $mapOptions);

    $data = [
        "activetab" => "haltes",
        "haltes" => $haltes,
        "d" => date('d'),
        "m" => date('m'),
        "Y" => date('Y'),
        "center" => $center, //FIXME Use $mapOptions in template and remove this.
        "map" => $mapOptions,
        "template" => "haltes.twig",
    ];

    render($data['template'], $data);
});
