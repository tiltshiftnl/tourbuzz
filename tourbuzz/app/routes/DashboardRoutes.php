<?php

/*************
 * Dashboard *
 *************/


/**
 * Dashboard.
 */
$app->get('/dashboard', function () use ($app) {

    $app->redirect("/dashboard/berichten");
});


/**
 * Login.
 */
$app->get('/dashboard/login', function () {

    render("dashboard/login.twig");
});


/**
 * Login post.
 */
$app->post('/dashboard/login', function () use ($app) {

    $fields = array(
        'username' => $app->request->post('username'),
        'password' => $app->request->post('password')
    );

    $apiResponse = $app->api->post("auth", $fields);

    switch ($apiResponse->statusCode) {
        case '200':
            $_SESSION['auth_token'] = $apiResponse->body['token'];
            $_SESSION['username']   = $app->request->post('username');

            if ( !empty($_SESSION['redirect_url']) ) {
                $target = $_SESSION['redirect_url'];
                unset($_SESSION['redirect_url']);
            } else {
                $target = "/dashboard/berichten";
            }

            $app->flash('success', 'Je bent nu ingelogd');
            $app->redirect($target);
            break;

        default:
            $app->flash('error', 'Het is niet gelukt helaas: '.$apiResponse->statusCode);
            $app->redirect("/dashboard/login");
    }

});


/**
 * Logout.
 */
$app->get('/dashboard/logout', function () use ($app) {

    if ( !empty($_SESSION['auth_token']) ) {
        $apiResponse = $app->api->delete("auth?token={$_SESSION['auth_token']}");
        unset($_SESSION['auth_token']);
        unset($_SESSION['username']);
        session_destroy();

        session_start();
        $app->flash('success', 'Je bent nu uitgelogd. Tot kijk!');
    }

    $app->redirect("/dashboard/login");
});


/**
 * Abonnees.
 */
$app->get('/dashboard/abonnees', function () use ($app) {

    if ( empty($_SESSION['username']) ) {
        $_SESSION['redirect_url'] = "/dashboard/abonnees";
        $app->flash('error', 'Eerst inloggen');
        $app->redirect("/dashboard/login");
    }

    $apiResponse = $app->api->get("mail?token={$_SESSION['auth_token']}");
    $abonnees = $apiResponse->body;

    $apiResponse = $app->api->get("telefoon?token={$_SESSION['auth_token']}");
    $nummers = count($apiResponse->body);

    $data = [
        "abonnees" => $abonnees,
        "nummers" => $nummers,
        "username" => $_SESSION['username'],
        "activetab" => "abonnees",
        "apikey" => getenv('GOOGLEMAPS_API_KEY'),
        "template" => "dashboard/abonnees.twig",
    ];

    render($data["template"], $data);
});


/************
 * Accounts *
 ************/


/**
 * Accounts.
 */
$app->get('/dashboard/accounts', function () use ($app) {

    if ( empty($_SESSION['username']) ) {
        $_SESSION['redirect_url'] = "/dashboard/accounts";
        $app->flash('error', 'Eerst inloggen');
        $app->redirect("/dashboard/login");
    }

    $apiResponse = $app->api->get("accounts?token={$_SESSION['auth_token']}");
    $accounts = $apiResponse->body;

    $data = [
        "navsection" => "Accounts",
        "accounts" => $accounts,
        "username" => $_SESSION['username'],
        "activetab" => "accounts",
        "apikey" => getenv('GOOGLEMAPS_API_KEY'),
        "template" => "dashboard/accounts.twig",
    ];

    render($data["template"], $data);
});


/**
 * Add new account.
 */
$app->post('/dashboard/accounts', function () use ($app) {

    $fields = array(
        'username' => $app->request->post('username'),
        'password' => $app->request->post('password'),
        'mail' => $app->request->post('mail'),
    );

    $app->api->setToken($_SESSION['auth_token']);
    $apiResponse = $app->api->post("accounts", $fields);

    switch ($apiResponse->statusCode) {
        case '200':
            $app->flash('success', 'Account is aangemaakt');
            break;

        default:
            $app->flash('error', 'Het is niet gelukt helaas: '.$apiResponse->statusCode);
    }

    $app->redirect("/dashboard/accounts");

});


/**
 * Account detail
 */
$app->get('/dashboard/accounts/:slug', function ($slug) use ($app) {

    if ( empty($_SESSION['username']) ) {
        $_SESSION['redirect_url'] = "/dashboard/accounts";
        $app->flash('error', 'Eerst inloggen');
        $app->redirect("/dashboard/login");
    }

    $apiResponse = $app->api->get("accounts/{$slug}?token={$_SESSION['auth_token']}");
    $account = $apiResponse->body;

    $data = [
        "account" => $account,
        "username" => $_SESSION['username'],
        "activetab" => "accounts",
        "apikey" => getenv('GOOGLEMAPS_API_KEY'),
        "template" => "dashboard/account-bewerken.twig",
    ];

    render($data["template"], $data);
});


/**
 * Edit account.
 */
$app->post('/dashboard/accounts/:slug', function ($slug) use ($app) {

   $fields = array(
        'username' => $app->request->post('username'),
        'password' => $app->request->post('password'),
        'mail' => $app->request->post('mail'),
        'create_notifications' => $app->request->post('create_notifications')
    );

    $app->api->setToken($_SESSION['auth_token']);
    $apiResponse = $app->api->put("accounts", $fields);

    switch ($apiResponse->statusCode) {
        case '200':
            $app->flash('success', 'Account aangepast!');
            break;

        default:
            $app->flash('error', 'Het is niet gelukt helaas: '.$apiResponse->statusCode);
    }

    $app->redirect("/dashboard/accounts");

});


/**
 * Account verwijderen.
 */
$app->get('/dashboard/accounts/:slug/verwijderen', function ($slug) use ($app) {

    if ( empty($_SESSION['username']) ) {
        $_SESSION['redirect_url'] = "/dashboard/accounts";
        $app->flash('error', 'Eerst inloggen');
        $app->redirect("/dashboard/login");
    }

    $app->api->setToken($_SESSION['auth_token']);
    $apiResponse = $app->api->delete("accounts/" . $slug);

    switch ($apiResponse->statusCode) {
        case '200':
            $app->flash('success', 'Account is verwijderd!');
            break;

        default:
            $app->flash('error', 'Het is niet gelukt helaas: '.$apiResponse->statusCode);
    }

    $app->redirect("/dashboard/accounts");

});


/***********************
 * Dashboard berichten *
 ***********************/


/**
 * Overview of messages (berichten).
 */
$app->get('/dashboard/berichten', function () use ($app, $image_api) {

    if ( empty($_SESSION['username']) ) {
        $_SESSION['redirect_url'] = "/dashboard/berichten";
        $app->flash('error', 'Eerst inloggen');
        $app->redirect("/dashboard/login");
    }

    $apiResponse = $app->api->get("berichten");

    $data = [
        "bericht" => [ // Default values for new message (bericht).
            "startdate" => date("Y-m-d"),
            "enddate" => date("Y-m-d"),
        ],
        "berichten" => $apiResponse->body['messages'],
        "image_api" => $image_api,
        "username" => $_SESSION['username'],
        "activetab" => "berichten",
        "token" => $_SESSION['auth_token'],
        "apikey" => getenv('GOOGLEMAPS_API_KEY'),
        "template" => "dashboard/berichten.twig",
    ];

    render($data['template'], $data);
});


/**
 * Add new message (bericht).
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
        'title_es' => $app->request->post('title_es'),
        'body_es' => $app->request->post('body_es'),
        'advice_es' => $app->request->post('advice_es'),
      	'startdate' => $app->request->post('startdate'),
      	'enddate' => $app->request->post('enddate'),
        'link' => $app->request->post('link'),
        'image_id' => $app->request->post('image_id'),
        'important' => $app->request->post('important'),
        'is_live' => $app->request->post('is_live'),
        'include_map' => !!$app->request->post('include_map'),
      	'sms_nl' => $app->request->post('sms_nl'),
      	'sms_en' => $app->request->post('sms_en'),
      	'sms_de' => $app->request->post('sms_de'),
        'sms_es' => $app->request->post('sms_es'),
    );

    // If not "dupliceren", then use the available id (if set) to update existing message.
    if ($app->request->post('submit') !== "dupliceren") {
        $fields['id'] = $app->request->post('id');
    }

    if ($app->request->post('include_location')) {
        $fields['location_lat'] = $app->request->post('location_lat');
        $fields['location_lng'] = $app->request->post('location_lng');
    } else {
        $fields['include_map'] = false;
    }

    // Title is a required field.
    if ( empty ($fields['title']) ) {
        $app->flashNow('error', 'Titel is niet ingevuld');

        $apiResponse = $app->api->get("berichten");
        $berichten = $apiResponse->body['messages'];

        $data = [
            "berichten" => $berichten,
            "bericht" => $fields,
            "image_api" => $image_api,
            "username" => $_SESSION['username'],
            "apikey" => getenv('GOOGLEMAPS_API_KEY'),
            "template" => "dashboard/berichten.twig",
            "activetab" => "berichten",
            "show_form" => true,
        ];

        render($data['template'], $data);
    } else {

        $app->api->setToken($_SESSION['auth_token']);
        $apiResponse = $app->api->post("berichten", $fields);

        switch ($apiResponse->statusCode) {
            case '200':
                $app->flash('success', 'Bericht toegevoegd');
                break;

            default:
                $app->flash('error', 'Het is niet gelukt helaas: '.$apiResponse->statusCode);
                $app->redirect("/dashboard/berichten");
        }

        if ( empty($app->request->post("id")) ) {
            $notes = "";
            if ( empty($fields['title_en']) ) {
                $notes .= " NOTE: Geen Engelse vertaling.";
            }
            if ( empty($fields['title_de']) ) {
                $notes .= " NOTE: Geen Duitse vertaling.";
            }
            if ( empty($fields['title_de']) ) {
                $notes .= " NOTE: Geen Spaanse vertaling.";
            }
            $app->flash('success', 'Bericht toegevoegd.' . $notes);
        } else if ($app->request->post("submit") === "dupliceren") {
            $app->flash('success', 'Bericht gedupliceerd');
        } else {
            $app->flash('success', 'Bericht opgeslagen');
        }

        $app->redirect("/dashboard/berichten");
    }
});


/**
 * Edit message (bericht).
 */
$app->get('/dashboard/berichten/:id', function ($id) use ($app, $image_api) {

    if ( empty($_SESSION['username']) ) {
        $_SESSION['redirect_url'] = "/dashboard/berichten/{$id}";
        $app->flash('error', 'Eerst inloggen');
        $app->redirect("/dashboard/login");
    }

    $apiResponse = $app->api->get("berichten");
    $berichten = $apiResponse->body['messages'];

    $data = [
        "bericht" => $berichten[$id],
        "berichten" => $berichten,
        "image_api" => $image_api,
        "username" => $_SESSION['username'],
        "activetab" => "berichten",
        "apikey" => getenv('GOOGLEMAPS_API_KEY'),
        "template" => "dashboard/berichten.twig",
    ];

    render($data['template'], $data);
});


/**
 * Delete messages (berichten).
 */
$app->post('/dashboard/berichten/verwijderen', function () use ($app) {

    $ids = $app->request->post('ids');
    $token = $_SESSION['auth_token'];

    $app->api->setToken($_SESSION['auth_token']);
    $res = $app->api->deleteBerichten("berichten", $ids); // FIXME
    if (!$res) {
        $app->flash('error', 'Mag niet! Unauthorized');
        $app->redirect("/dashboard/berichten");
    }

    $app->flash('success', 'Bericht(en) verwijderd');
    $app->redirect("/dashboard/berichten");
});


/**
 * Mapping parkeerplaatsen.
 */
$app->get('/dashboard/mapping-parkeren', function () use ($app) {

   if ( empty($_SESSION['username']) ) {
        $_SESSION['redirect_url'] = "/dashboard/berichten/{$id}";
        $app->flash('error', 'Eerst inloggen');
        $app->redirect("/dashboard/login");
    }

    $apiResponse = $app->api->get("parkeerplaatsen");
    $parkeerplaatsen = $apiResponse->body['parkeerplaatsen'];

    $app->api->setToken($_SESSION['auth_token']);
    $apiResponse = $app->api->get("vialis");
    $vialis = $apiResponse->body;

    $data = [
        "parkeerplaatsen" => $parkeerplaatsen,
        "vialis" => $vialis,
        "username" => $_SESSION['username'],
        "activetab" => "mapping-parkeren",
        "apikey" => getenv('GOOGLEMAPS_API_KEY'),
        "template" => "dashboard/mapping-parkeren.twig",
    ];

    render($data['template'], $data);
});


/**
 * Mapping parkeerplaatsen post.
 */
$app->post('/dashboard/mapping-parkeren', function () use ($app) {

    $apiResponse = $app->api->get("parkeerplaatsen");
    $parkeerplaatsen = $apiResponse->body['parkeerplaatsen'];

    $token = $_SESSION['auth_token'];
    $app->api->setToken($token);
    $apiResponse = $app->api->get("vialis");
    $vialis = $apiResponse->body;

    foreach ($parkeerplaatsen as $parkeerplaats) {

        if ( !empty($app->request->post($parkeerplaats['nummer'])) ) {
            $vialis_id = $app->request->post($parkeerplaats['nummer']);
        } else {
            $vialis_id = 'NULL';
        }

        $fields = array(
            'parkeerplaats' => $parkeerplaats['nummer'],
            'id' => $vialis_id
        );
        $app->api->setToken($token);
        $apiResponse = $app->api->post("vialis", $fields);
    }

    switch ($apiResponse->statusCode) {
        case '200':
            $app->flash('success', 'Koppeling opgeslagen!');
            break;

        default:
            $app->flash('error', 'Het is niet gelukt helaas: '.$apiResponse->statusCode);
    }

    $app->redirect("/dashboard/mapping-parkeren");
});


/**
 * Dashboard wildcard redirect.
 */
$app->get('/dashboard/(:wildcard+)', function () use ($app) {
    $app->redirect("/dashboard/berichten");
});
