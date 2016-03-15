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
 * Halte profiel
 */
$app->get('/haltes', function () use ($app, $apiRoot) {

    $haltes = $app->api->get("haltes");

    $data = [
        "haltes" => $haltes['haltes'],
        "template" => "overzichtskaart.twig",
    ];

    render($data['template'], $data);
});


/**
 * Halte profiel
 */
$app->get('/haltes/:slug', function ($slug) use ($app, $apiRoot) {

    $halte = $app->api->get("haltes/{$slug}");

    $data = [
        "record" => $halte['halte'],
        "template" => "halte.twig",
    ];

    render($data['template'], $data);
});


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
        "template" => "home.twig",
    ];
    render($data['template'], $data);
})->name("home");

