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
function sendNewBerichtMail($berichtId, $berichtTitle) {
    global $mailTo, $buzzProc, $buzzUri;
    mail(
        $mailTo,
        "Er is een nieuw bericht aangemaakt in tour buzz",
        "Er is een nieuw bericht aangemaakt in tour buzz door {$_SESSION['username']} met de titel \"{$berichtTitle}\".\r\n" .
        "Bekijk het bericht op {$buzzProc}{$buzzUri}/dashboard/berichten/{$berichtId}",
        "From: dashboard@{$buzzUri}\r\n" .
        "Reply-To: noreply@{$buzzUri}\r\n" .
        "X-Mailer: PHP/" . phpversion()
   );
}

/**
 * FIXME Split into map and filter function.
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
 * Before
 */
$app->hook('slim.before', function() use ($app) {
    if (empty($_SESSION['lang'])) {
        $_SESSION['lang'] = 'nl';
    }
    $lang = $app->request()->params('lang');
    if (isset($lang) && in_array($lang, array('nl','de','en'))) {
        $_SESSION['lang'] = $lang;
    }
});


/**
 * Home
 */
$app->get('/', function () use ($app, $apiRoot) {

    //if ( !empty($_SESSION['firstvisit']) ) {
        $app->redirect(date('/Y/m/d'));
    //}

    //$_SESSION['firstvisit'] = true;

    //$data = [
    //    "lang" => $_SESSION['lang'],
    //    "redirect" => date('/Y/m/d'),
    //    "template" => "splash.twig",
    //];

    //render($data['template'], $data);
});


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

    $haltes = locationItemsToMap($haltes, $mapOptions, true);

    $data = [
        "lang" => $_SESSION['lang'],
        "activetab" => "haltes",
        "haltes" => $haltes,
        "j" => date('j'),
        "d" => date('d'),
        "m" => date('m'),
        "Y" => date('Y'),
        "center" => $center, //FIXME Use $mapOptions in template and remove this.
        "map" => $mapOptions,
        "template" => "haltes.twig",
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
        "zoom" => 16,
        "scale" => 2,
        "center" => $center,
    ];

    $haltes = locationItemsToMap($haltes, $mapOptions);

    $data = [
        "lang" => $_SESSION['lang'],
        "activetab" => "haltes",
        "record" => $halte,
        "haltes" => $haltes,
        "map" => $mapOptions,
        "center" => $center, //FIXME Use $mapOptions in template and remove this.
        "template" => "halte.twig",
        "j" => date('j'),
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
        "lang" => $_SESSION['lang'],
        "activetab" => "parkeren",
        "parkeerplaatsen" => $parkeerplaatsen,
        "j" => date('j'),
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
        "lang" => $_SESSION['lang'],
        "activetab" => "parkeren",
        "record" => $parkeerplaats,
        "parkeerplaatsen" => $parkeerplaatsen,
        "map" => $mapOptions,
        "center" => $center, //FIXME Use $mapOptions in template and remove this.
        "template" => "parkeerplaats.twig",
        "j" => date('j'),
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

/**
 * Overview formats navigatie apparaten
 */
$app->get('/downloads', function () use ($app) {
    render("downloads.twig");
});

/*****************
/* Admin Routes
 ****************/

/**
 * Dashboard
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
    unset($_SESSION['username']);
    session_destroy();

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

    if ( !empty($_SESSION['redirect_url']) ) {
        $target = $_SESSION['redirect_url'];
        unset($_SESSION['redirect_url']);
        $app->redirect($target);
    } else {
        $app->redirect("/dashboard/berichten");
    }
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
        "bericht" => [ // defaults
            "startdate" => date("Y-m-d"),
            "enddate" => date("Y-m-d"),
        ],
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
      	'title_de' => $app->request->post('title_de'),
      	'body_de' => $app->request->post('body_de'),
        'advice_de' => $app->request->post('advice_de'),
      	'startdate' => $app->request->post('startdate'),
      	'enddate' => $app->request->post('enddate'),
        'link' => $app->request->post('link'),
        'image_url' => $app->request->post('image_url'),
        'important' => $app->request->post('important'),
        'is_live' => $app->request->post('is_live'),
        'include_map' => !!$app->request->post('include_map'),
    );

    if ($app->request->post('submit') !== "dupliceren") {
        $fields['id'] = $app->request->post('id');
    }

    if ($app->request->post('include_location')) {
        $fields['location_lat'] = $app->request->post('location_lat');
        $fields['location_lng'] = $app->request->post('location_lng');
    }

    if ( empty ($fields['title']) ) {
        $app->flashNow('error', 'Titel is niet ingevuld');

        $berichten = $app->api->get("berichten");

        $data = [
            "berichten" => $berichten['messages'],
            "bericht" => $fields,
            "image_api" => $image_api,
            "api" => $app->api->getApiRoot(),
            "username" => $_SESSION['username'],
            "template" => "dashboard/berichten.twig",
            "show_form" => true,
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

        // Mail when a new bericht is added successfully.
        if (empty($app->request->post("id"))) {
            $notes = "";
            if ( empty($fields['title_en']) ) { $notes .="NOTE: Geen Engelse vertaling. "; }
            if ( empty($fields['title_de']) ) { $notes .="NOTE: Geen Duitse vertaling. "; }
            $app->flash('success', 'Bericht toegevoegd. '.$notes);
            sendNewBerichtMail($res['id'], $fields['title']);
        } else if ($app->request->post("submit") === "dupliceren") {
            $app->flash('success', 'Bericht gedupliceerd');
        } else {
            $app->flash('success', 'Bericht opgeslagen');
        }

        $app->redirect("/dashboard/berichten");
    }
})->name("berichten");


/**
 * Berichten bewerken
 */
$app->get('/dashboard/berichten/:id', function ($id) use ($app, $image_api) {

    $berichten = $app->api->get("berichten");

    if ( empty($_SESSION['username']) ) {
        $_SESSION['redirect_url'] = "/dashboard/berichten/{$id}";
        $app->flash('error', 'Eerst inloggen');
        $app->redirect("/dashboard/login");
    }

    $data = [
        "test" => "world",
        "bericht" => $berichten['messages'][$id],
        "berichten" => $berichten['messages'],
        "image_api" => $image_api,
        "username" => $_SESSION['username'],
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
 * Dashboard wildcard redirect
 */
$app->get('/dashboard/(:wildcard+)', function () use ($app) {
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
    $j = date('j', strtotime("{$y}-{$m}-{$d}"));

    $day = array (
        'maandag', 'dinsdag', 'woensdag', 'donderdag', 'vrijdag', 'zaterdag', 'zondag'
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

    $berichten = locationItemsToMap($berichten, $mapOptions, false);

    $data = [
        "activetab" => "berichten",
        "lang" => $_SESSION['lang'],
        "berichten" => $berichten,
        "volgende" => $volgende,
        "vorige" => $vorige,
        "datestring" => "{$y}-{$m}-{$d}",
        "dag" => $dag,
        "j" => $j,
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
        "adamlogo" => true,
        "template" => "home.twig",
    ];
    render($data['template'], $data);
})->name("home");


/**
 * Dag details
 */
$app->get('/:y/:m/:d/details', function ($y, $m, $d) use ($app, $analytics, $image_api) {

    $res = $app->api->get("berichten/{$y}/{$m}/{$d}");

    $berichten = array_filter($res['messages'], function ($bericht) {
        return !empty($bericht['is_live']);
    });

    usort($berichten, function ($b1, $b2) {
        return $b1['important'] < $b2['important'];
    });

    $volgende = "/".str_replace('-', '/', $res['_nextDate']);
    $vorige   = "/".str_replace('-', '/', $res['_prevDate']);

    $N = date('N', strtotime("{$y}-{$m}-{$d}"));

    $day = array (
        'maandag', 'dinsdag', 'woensdag', 'donderdag', 'vrijdag', 'zaterdag', 'zondag'
    );

    $dag = translate($day[(int)$N - 1]);

    foreach ($berichten as &$bericht) {
        if ( !empty($bericht['location']) ) {
            $center = $bericht['location'];

            $mapOptions = [
                "width" => 420,
                "height" => 350,
                "zoom" => 15,
                "scale" => 2,
                "center" => $center,
            ];

            $bericht['map'] = $mapOptions;
            $bericht['rel_loc'] = array ('dX' => 50,'dY' => 50);
            // locationItemsToMap($bericht, $mapOptions, false)
        }
    }

    $data = [
        "activetab" => "berichten",
        "lang" => $_SESSION['lang'],
        "berichten" => $berichten,
        "volgende" => $volgende,
        "vorige" => $vorige,
        "datestring" => "{$y}-{$m}-{$d}",
        "dag" => $dag,
        "j" => date('j'),
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
        "template" => "details.twig",
    ];
    render($data['template'], $data);
});

/**
 * Enkel bericht
 */
$app->get('/bericht/:id', function ($id) use ($app) {

    $res = $app->api->get("berichten/{$id}");

    $data = [
        "bericht" => $res['message']
    ];

    render("bericht.twig", $data);
});

/**
 * RSS
 */
$app->get('/rss', function () use ($app) {

    list($Y, $m, $d) = explode("-", date("Y-m-d"));
    $dateurlstring = "{$Y}/{$m}/{$d}";

    $res = $app->api->get("berichten/{$dateurlstring}");

    $berichten = array_filter($res['messages'], function ($bericht) {
        return !empty($bericht['is_live']);
    });

    usort($berichten, function ($b1, $b2) {
        return $b1['important'] < $b2['important'];
    });

    $data = [
        "berichten" => $berichten,
        "datum" => date(DATE_ISO8601),
        "dateurlstring" => $dateurlstring,
    ];

    render("rss.twig", $data, ["Content-type" => "application/xml"]);
});
