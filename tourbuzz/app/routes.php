<?php

require_once("config/config.php");
$localConfigFilePath = __DIR__ . "/config/config_local.php";
if (file_exists($localConfigFilePath)) {
	require_once($localConfigFilePath);
}

use GuzzleHttp\Client;
use GuzzleHttp\Message\Request;
use GuzzleHttp\Message\Response;

require_once("loadApiSingleton.php");

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


/*****************
/* Admin Routes
 ****************/

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
 * Berichten get
 */
$app->get('/dashboard/berichten', function () use ($app, $image_api) {

    $berichten = $app->api->get("berichten");

    $data = [
        "berichten" => $berichten['messages'],
        "image_api" => $image_api,
        "api" => $app->api->getApiRoot(),
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

        $app->api->post("berichten", $fields);

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
    $berichten = $app->api->delete("berichten", $ids);

    $app->flash('success', 'Bericht(en) verwijderd');
    $app->redirect("/dashboard/berichten");

});

/**
 * Dag
 */
$app->get('/:y/:m/:d', function ($y, $m, $d) use ($app, $analytics, $image_api) {

    $berichten = $app->api->get("berichten/{$y}/{$m}/{$d}");

    $volgende = "/".str_replace('-', '/', $berichten['_nextDate']);
    $vorige   = "/".str_replace('-', '/', $berichten['_prevDate']);

    $cruisekalender = $app->api->get("cruisekalender/{$y}/{$m}/{$d}");

    $N = date('N', strtotime("{$y}-{$m}-{$d}"));

    $day = array (
        'ma', 'di', 'wo', 'do', 'vr', 'za', 'zo'
    );

    $dag = translate($day[(int)$N - 1]);

    $data = [
        "lang" => $_SESSION['lang'],
        "berichten" => $berichten['messages'],
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

