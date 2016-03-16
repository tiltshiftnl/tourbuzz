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

    $res = $app->api->get("berichten/".date('Y')."/".date('m')."/".date('d'));

    $berichten = array_filter($res['messages'], function ($bericht) {
        return !empty($bericht['is_live']);
    });

    // Pixels per dLat and dLng for Amsterdam (approx) at zoomlevels.
    $ppd = [
        "lat" => [
            "12" => 4770.821985494679,
            "13" => 9541.643998239988,
            "14" => 19083.28801010367,
        ],
        "lng" => [
            "12" => 2912.711111111111,
            "13" => 5825.422222222222,
            "14" => 11650.844444444445,
        ],
    ];

    $res = $app->api->get("parkeerplaatsen");
    $parkeerplaatsen = $res['parkeerplaatsen'];

    $center = [
        "lat" => 52.372981,
        "lng" => 4.901327,
    ];

    $mapOptions = [
        "width" => 420,
        "height" => 350,
        "zoom" => 12,
        "scale" => 2,
    ];

    // Calculate relative positions on the map.
    $parkeerplaatsen = array_map(function ($parkeerplaats) use($center, $mapOptions, $ppd) {
        $dLat = $parkeerplaats['location']['lat'] - $center['lat'];
        $dLng = $parkeerplaats['location']['lng'] - $center['lng'];
        $dY = $dLat * 100 * $ppd['lat'][$mapOptions['zoom']];
        $dX = $dLng * 100 * $ppd['lng'][$mapOptions['zoom']];
        $parkeerplaats['rel_loc'] = [
            "dX" => 50 + ($dX / ($mapOptions['width'])),
            "dY" => 50 - ($dY / ($mapOptions['height'])),
        ];
        return $parkeerplaats;
    }, $parkeerplaatsen);

    $parkeerplaatsen = array_filter($parkeerplaatsen, function ($parkeerplaats) {
        return
            !($parkeerplaats['rel_loc']['dX'] > 100) &&
            !($parkeerplaats['rel_loc']['dX'] < 0) &&
            !($parkeerplaats['rel_loc']['dY'] > 100) &&
            !($parkeerplaats['rel_loc']['dY'] < 0);
    });

    $data = [
        "parkeerplaatsen" => $parkeerplaatsen,
        "d" => date('d'),
        "m" => date('m'),
        "Y" => date('Y'),
        "center" => $center,
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

    uasort($haltes, function ($h1, $h2) use ($halte) {
        $d1 = distance(
            $halte['location']['lat'],
            $halte['location']['lng'],
            $h1['location']['lat'],
            $h1['location']['lng']
        );
        $d2 = distance(
            $halte['location']['lat'],
            $halte['location']['lng'],
            $h2['location']['lat'],
            $h2['location']['lng']
        );
        return $d1 > $d2;
    });

    $data = [
        "record" => $halte,
        "haltes" => $haltes,
        "template" => "halte.twig",
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

    $parkeerplaats = $app->api->get("parkeerplaatsen/{$slug}");

    $data = [
        "record" => $parkeerplaats['parkeerplaats'],
        "template" => "parkeerplaats.twig",
    ];

    render($data['template'], $data);
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

    //FIXME Remove this call to cruise calendar?

    $cruisekalender = $app->api->get("cruisekalender/{$y}/{$m}/{$d}");

    $N = date('N', strtotime("{$y}-{$m}-{$d}"));

    $day = array (
        'ma', 'di', 'wo', 'do', 'vr', 'za', 'zo'
    );

    $dag = translate($day[(int)$N - 1]);


   // Pixels per dLat and dLng for Amsterdam (approx) at zoomlevels.
    $ppd = [
        "lat" => [
            "12" => 4770.821985494679,
            "13" => 9541.643998239988,
            "14" => 19083.28801010367,
        ],
        "lng" => [
            "12" => 2912.711111111111,
            "13" => 5825.422222222222,
            "14" => 11650.844444444445,
        ],
    ];

   // $res = $app->api->get("haltes");
   //$haltes = $res['haltes'];

    $center = [
        "lat" => 52.372981,
        "lng" => 4.901327,
    ];

    $mapOptions = [
        "width" => 420,
        "height" => 350,
        "zoom" => 12,
        "scale" => 2,
    ];

    // Calculate relative positions on the map.
    $berichten = array_map(function ($bericht) use($center, $mapOptions, $ppd) {
        $dLat = $bericht['location']['lat'] - $center['lat'];
        $dLng = $bericht['location']['lng'] - $center['lng'];
        $dY = $dLat * 100 * $ppd['lat'][$mapOptions['zoom']];
        $dX = $dLng * 100 * $ppd['lng'][$mapOptions['zoom']];
        $bericht['rel_loc'] = [
            "dX" => 50 + ($dX / ($mapOptions['width'])),
            "dY" => 50 - ($dY / ($mapOptions['height'])),
        ];
        return $bericht;
    }, $berichten);

    $berichten = array_filter($berichten, function ($bericht) {
        return
            !($bericht['rel_loc']['dX'] > 100) &&
            !($bericht['rel_loc']['dX'] < 0) &&
            !($bericht['rel_loc']['dY'] > 100) &&
            !($bericht['rel_loc']['dY'] < 0);
    });

    $data = [
        "lang" => $_SESSION['lang'],
        "berichten" => $berichten,
        "volgende" => $volgende,
        "vorige" => $vorige,
        "datestring" => "{$y}-{$m}-{$d}",
        "dag" => $dag,
        "d" => $d,
        "m" => $m,
        "y" => $y,
        "api" => $app->api->getApiRoot(),
        "image_api" => $image_api,
        "analytics" => $analytics,
        "cruisekalender" => $cruisekalender['items'],
        "timestamp" => $res['_timestamp'],
        "center" => $center,
        "map" => $mapOptions,
        "template" => "home.twig",
    ];
    render($data['template'], $data);
})->name("home");

/**
 * Haltes
 */
$app->get('/haltes', function () use ($app, $apiRoot) {

    $res = $app->api->get("berichten/".date('Y')."/".date('m')."/".date('d'));

    $berichten = array_filter($res['messages'], function ($bericht) {
        return !empty($bericht['is_live']);
    });

    // Pixels per dLat and dLng for Amsterdam (approx) at zoomlevels.
    $ppd = [
        "lat" => [
            "12" => 4770.821985494679,
            "13" => 9541.643998239988,
            "14" => 19083.28801010367,
        ],
        "lng" => [
            "12" => 2912.711111111111,
            "13" => 5825.422222222222,
            "14" => 11650.844444444445,
        ],
    ];

    $res = $app->api->get("haltes");
    $haltes = $res['haltes'];

    $center = [
        "lat" => 52.372981,
        "lng" => 4.901327,
    ];

    $mapOptions = [
        "width" => 420,
        "height" => 350,
        "zoom" => 14,
        "scale" => 2,
    ];

    // Calculate relative positions on the map.
    $haltes = array_map(function ($halte) use($center, $mapOptions, $ppd) {
        $dLat = $halte['location']['lat'] - $center['lat'];
        $dLng = $halte['location']['lng'] - $center['lng'];
        $dY = $dLat * 100 * $ppd['lat'][$mapOptions['zoom']];
        $dX = $dLng * 100 * $ppd['lng'][$mapOptions['zoom']];
        $halte['rel_loc'] = [
            "dX" => 50 + ($dX / ($mapOptions['width'])),
            "dY" => 50 - ($dY / ($mapOptions['height'])),
        ];
        return $halte;
    }, $haltes);

    $haltes = array_filter($haltes, function ($halte) {
        return
            !($halte['rel_loc']['dX'] > 100) &&
            !($halte['rel_loc']['dX'] < 0) &&
            !($halte['rel_loc']['dY'] > 100) &&
            !($halte['rel_loc']['dY'] < 0);
    });

    $data = [
        "haltes" => $haltes,
        "d" => date('d'),
        "m" => date('m'),
        "Y" => date('Y'),
        "center" => $center,
        "map" => $mapOptions,
        "template" => "haltes.twig",
    ];

    render($data['template'], $data);
});
