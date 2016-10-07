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
 * Sends an e-mail about a newly created message (bericht).
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
    if (isset($lang) && in_array($lang, array('nl','de','en'))) {
        $_SESSION['lang'] = $lang;
    }
});

/**
 * Home redirects to current date.
 */
$app->get('/', function () use ($app, $apiRoot) {
    $app->redirect(date('/Y/m/d'));
});


/**
 * Overview of busstops (haltes).
 */
$app->get('/haltes', function () use ($app, $analytics) {

    $apiResponse = $app->api->get("haltes");
    $haltes = $apiResponse->body['haltes'];

    // Amsterdam Center Point.
    $center = [
        "lat" => 52.372981,
        "lng" => 4.901327,
    ];

    $mapOptions = [
        "width" => 420,
        "height" => 350,
        "zoom" => 14, // Google Maps zoom level.
        "scale" => 2, // Double resolution for retina display.
        "center" => $center,
    ];

    //$haltes = locationItemsToMap($haltes, $mapOptions, true);

    $data = [
        "m" => date('m'),
        "d" => date('d'),
        "Y" => date('Y'),
        "lang" => $_SESSION['lang'],
        "activetab" => "haltes-parkeerplaatsen",
        "haltes" => $haltes,
        "map" => $mapOptions,
        "analytics" => $analytics,
        "apikey" => "AIzaSyDlLcl2JExZANAIdG7UVcpqj3iTT1KL76Q",
        "template" => "haltes.twig",
    ];

    render($data['template'], $data);
});


/**
 * Alles
 */
$app->get('/haltes-parkeerplaatsen', function () use ($app, $analytics) {

    $apiResponse = $app->api->get("haltes");
    $haltes = $apiResponse->body['haltes'];

    $apiResponse = $app->api->get("parkeerplaatsen");
    $parkeerplaatsen = $apiResponse->body['parkeerplaatsen'];

    // Amsterdam Center Point.
    $center = [
        "lat" => 52.372981,
        "lng" => 4.901327,
    ];

    $mapOptions = [
        "width" => 420,
        "height" => 350,
        "zoom" => 14, // Google Maps zoom level.
        "scale" => 2, // Double resolution for retina display.
        "center" => $center,
    ];

    //$haltes = locationItemsToMap($haltes, $mapOptions, true);

    $data = [
        "m" => date('m'),
        "d" => date('d'),
        "Y" => date('Y'),
        "lang" => $_SESSION['lang'],
        "activetab" => "haltes-parkeerplaatsen",
        "haltes" => $haltes,
        "parkeerplaatsen" => $parkeerplaatsen,
        "map" => $mapOptions,
        "analytics" => $analytics,
        "apikey" => "AIzaSyDlLcl2JExZANAIdG7UVcpqj3iTT1KL76Q",
        "template" => "haltes-parkeerplaatsen.twig",
    ];

    render($data['template'], $data);
});


/**
 * Single busstop (halte).
 */
$app->get('/haltes/:slug', function ($slug) use ($app, $analytics) {

    $apiResponse = $app->api->get("haltes");
    $haltes = $apiResponse->body['haltes'];
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
        "m" => date('m'),
        "d" => date('d'),
        "Y" => date('Y'),
        "lang" => $_SESSION['lang'],
        "activetab" => "haltes-parkeerplaatsen",
        "record" => $halte,
        "haltes" => $haltes,
        "map" => $mapOptions,
        "analytics" => $analytics,
        "template" => "halte.twig",
    ];

    render($data['template'], $data);
});


/**
 * Overview of busparkings (parkeerplaatsen).
 */
$app->get('/parkeren', function () use ($app, $analytics) {

    $apiResponse = $app->api->get("parkeerplaatsen");
    $parkeerplaatsen = $apiResponse->body['parkeerplaatsen'];

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

    $parkeerplaatsen = locationItemsToMap($parkeerplaatsen, $mapOptions);

    $data = [
        "m" => date('m'),
        "d" => date('d'),
        "Y" => date('Y'),
        "lang" => $_SESSION['lang'],
        "activetab" => "haltes-parkeerplaatsen",
        "parkeerplaatsen" => $parkeerplaatsen,
        "map" => $mapOptions,
        "analytics" => $analytics,
        "template" => "parkeerplaatsen.twig",
    ];

    render($data['template'], $data);
});


/**
 * Single busparking (parkeerplaats).
 */
$app->get('/parkeerplaatsen/:slug', function ($slug) use ($app, $analytics) {

    $apiResponse = $app->api->get("parkeerplaatsen");
    $parkeerplaatsen = $apiResponse->body['parkeerplaatsen'];
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
        "m" => date('m'),
        "d" => date('d'),
        "Y" => date('Y'),
        "lang" => $_SESSION['lang'],
        "activetab" => "haltes-parkeerplaatsen",
        "record" => $parkeerplaats,
        "parkeerplaatsen" => $parkeerplaatsen,
        "map" => $mapOptions,
        "analytics" => $analytics,
        "template" => "parkeerplaats.twig",
    ];

    render($data['template'], $data);
});



/******************************
 * Wachtwoord vergeten Routes *
 ******************************/


/**
 * Wachtwoord vergeten
 */
$app->get('/wachtwoordvergeten', function () use ($app) {

    $data = [
        "template" => "wachtwoord-vergeten.twig",
    ];

    render($data["template"], $data);

});

/**
 * Wachtwoord vergeten post
 */
$app->post('/wachtwoordvergeten', function () use ($app) {

    $fields = array(
        'username' => $app->request->post('username'),
    );

    $apiResponse = $app->api->post("vergeten", $fields);

    switch ($apiResponse->statusCode) {
        case '200':
            $app->flash('success', 'Mail verzonden');
            break;

        default:
            $app->flash('error', 'Het is niet gelukt helaas: '.$apiResponse->statusCode);
    }

    $app->redirect("/wachtwoordvergeten");

});


/**
 * Wachtwoord vergeten instellen.
 */
$app->get('/wachtwoordvergeten/:token', function ($token) use ($app) {

    $apiResponse = $app->api->get("vergeten/{$token}");

    if ( empty($apiResponse->body['username']) ) {
        $app->flash('error', 'Ongeldige of verlopen token');
        $app->redirect('/wachtwoordvergeten');
    }

    $data = [
        "template" => "wachtwoord-instellen.twig",
    ];

    render($data["template"], $data);
});


/**
 * Wachtwoord instellen
 */
$app->post('/wachtwoordvergeten/:token', function ($token) use ($app) {

    $fields = array(
        'token' => $token,
        'password' => $app->request->post('password'),
    );

    $apiResponse = $app->api->put("vergeten", $fields);

    switch ($apiResponse->statusCode) {
        case '200':
            $app->flash('success', 'Account aangepast!');
            $app->redirect("/dashboard/login");
            break;

        default:
            $app->flash('error', 'Het is niet gelukt helaas: '.$apiResponse->statusCode);
    }

    $app->redirect("/wachtwoordvergeten/{$token}");

});


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


/********************
 * Dashboard Routes *
 *******************/


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
 * Login post
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



/************
 * Abonnees *
 ***********/

/**
 * Abonnees
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
        "template" => "dashboard/abonnees.twig",
    ];

    render($data["template"], $data);
});



/************
 * Accounts *
 ***********/


/**
 * Accounts
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
        "template" => "dashboard/account-bewerken.twig",
    ];

    render($data["template"], $data);
});


/**
 * Edit account
 */
$app->post('/dashboard/accounts/:slug', function ($slug) use ($app) {

   $fields = array(
        'username' => $app->request->post('username'),
        'password' => $app->request->post('password'),
        'mail' => $app->request->post('mail'),
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
 * Account verwijderen
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

        // Mail when a new bericht is added successfully.
        if ( empty($app->request->post("id")) ) {
            $notes = "";
            if ( empty($fields['title_en']) ) {
                $notes .= " NOTE: Geen Engelse vertaling.";
            }
            if ( empty($fields['title_de']) ) {
                $notes .= " NOTE: Geen Duitse vertaling.";
            }
            //sendNewBerichtMail($apiResponse->body['id'], $fields['title']);
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
 * Current token
 */
$app->get('/token', function () use ($app) {
    die($_SESSION['auth_token']);
});

/**
 * Dashboard wildcard redirect.
 */
$app->get('/dashboard/(:wildcard+)', function () use ($app) {
    $app->redirect("/dashboard/berichten");
});



/***************
 * SMS *
 ***************/


/**
 * SMS aanmelden.
 */
$app->get('/sms-aanmelden', function () use ($app) {

    $data = [
        "lang" => $_SESSION['lang'],
        "template" => "sms-aanmelden.twig",
    ];

    render($data['template'], $data);
});

/**
 * SMS aanmelden.
 */
$app->post('/sms-aanmelden', function () use ($app) {

    $fields = array(
        'number' => $app->request->post('international'), // gebruik de hidden value van getNumber
      	'language' => $app->request->post('language'),
    );

    $apiResponse = $app->api->post("telefoon", $fields);

    switch ($apiResponse->statusCode) {
        case '200':
            $app->flash('success', 'U bent nu aangemeld voor SMS berichten van Tour Buzz');
            break;

        default:
            $app->flash('error', 'Het is niet gelukt helaas: '.$apiResponse->statusCode);
            $app->redirect("/sms-aanmelden");
    }

    $app->redirect(date('/Y/m/d'));
});


/**
 * SMS afmelden.
 */
$app->get('/sms-afmelden', function () use ($app) {

    $data = [
        "lang" => $_SESSION['lang'],
        "template" => "sms-afmelden.twig",
    ];

    render($data['template'], $data);
});

/**
 * SMS afmelden.
 */
$app->post('/sms-afmelden', function () use ($app) {

    $fields = array(
        'number' => $app->request->post('international'), // gebruik de hidden value van getNumber
    );

    $apiResponse = $app->api->delete("telefoon", $fields);

    switch ($apiResponse->statusCode) {
        case '200':
            $app->flash('success', 'We hebben uw nummer verwijderd');
            break;

        default:
            $app->flash('error', 'Het is niet gelukt helaas: '.$apiResponse->statusCode);
            $app->redirect("/sms-afmelden");
    }

    $app->redirect(date('/Y/m/d'));
});

/***************
 * Berichten *
 ***************/


/**
 * berichtenservice aanmelden.
 */
$app->get('/berichtenservice', function () use ($app) {

    $data = [
        "lang" => $_SESSION['lang'],
        "template" => "berichtenservice-aanmelden.twig",
    ];

    render($data['template'], $data);
});


/**
 * Nieuwsbrief aanmelden.
 */
$app->post('/berichtenservice', function () use ($app) {

    $fields = array(
        'mail' => $app->request->post('mail'),
      	'name' => $app->request->post('name'),
      	'organisation' => $app->request->post('organisation'),
      	'language' => $app->request->post('language'),
    );

    //$app->api->setToken($_SESSION['auth_token']);
    $apiResponse = $app->api->post("mail", $fields);

    switch ($apiResponse->statusCode) {
        case '200':
            $app->flash('success', translate('We hebben u een mail gestuurd. Klik op de link in het mailbericht.'));
            break;
        case '406':
            $app->flash('error', 'Dit email adres is al aangemeld');
            $app->redirect('/berichtenservice');
            break;

        default:
            $app->flash('error', 'Het is niet gelukt helaas: '.$apiResponse->statusCode);
            $app->redirect("/berichtenservice");
    }

    $app->redirect(date('/Y/m/d'));
});


/**
 * berichtenservice mail bevestigen.
 */
$app->get('/mailbevestigen/:token', function ($token) use ($app) {

    $apiResponse = $app->api->get("mail/{$token}");

    switch ($apiResponse->statusCode) {
        case '200':
            $app->flash('success', 'Dank voor uw bevestiging. U ontvangt nu tweewekelijks Tour Buzz berichten.');
            $app->redirect(date('/Y/m/d'));
            break;

        default:
            $app->flash('error', 'Het is niet gelukt helaas: '.$apiResponse->statusCode);
            $app->redirect("/berichtenservice");
    }

});


/**
 * Nieuwsbrief afmelden.
 */
$app->get('/berichtenservice-afmelden', function () use ($app) {

    $data = [
        "lang" => $_SESSION['lang'],
        "template" => "berichtenservice-afmelden.twig",
    ];

    render($data['template'], $data);
});


/**
 * Nieuwsbrief afmelden.
 */
$app->post('/berichtenservice-afmelden', function () use ($app) {

    $fields = array(
        'mail' => $app->request->post('mail'),
    );

    //$app->api->setToken($_SESSION['auth_token']);
    $apiResponse = $app->api->post("mail/unsubscribe", $fields);

    switch ($apiResponse->statusCode) {
        case '200':
            $app->flash('success', 'We hebben u een mail gestuurd. Klik op de link in uw mailbericht.');
            break;

        default:
            //die(print_r($apiResponse->body));
            $app->flash('error', 'Het is niet gelukt helaas: '.$apiResponse->statusCode);
            $app->redirect("/berichtenservice-afmelden");
    }

    $app->redirect('/berichtenservice-afmelden');
});


/**
 * berichtenservice afmelden bevestigen.
 */
$app->get('/mailannuleren/:token', function ($token) use ($app) {

    $apiResponse = $app->api->get("mail/unsubscribe/{$token}");

    switch ($apiResponse->statusCode) {
        case '200':
            $app->flash('success', 'Uw afmelding is verwerkt');
            $app->redirect('/berichtenservice');
            break;

        default:
            $app->flash('error', 'Het is niet gelukt helaas: '.$apiResponse->statusCode);
            $app->redirect("/berichtenservice");
    }

});


/**
 * Overview of messages for a single day.
 */
$app->get('/:y/:m/:d', function ($y, $m, $d) use ($app, $analytics, $image_api) {

    //$res = $app->api->get("berichten/{$y}/{$m}/{$d}");

    list($d) = explode('?', $d);

    $apiResponse = $app->api->get("berichten/{$y}/{$m}/{$d}");

    $berichten = array_filter($apiResponse->body['messages'], function ($bericht) {
        return !empty($bericht['is_live']);
    });

    usort($berichten, function ($b1, $b2) {
        return $b1['important'] < $b2['important'];
    });

    //$volgende = "/".str_replace('-', '/', $res['_nextDate']);
    //$vorige   = "/".str_replace('-', '/', $res['_prevDate']);

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
        //"volgende" => $volgende,
        //"vorige" => $vorige,
        "datestring" => "{$y}-{$m}-{$d}",
        "dag" => $dag,
        "j" => $j,
        "d" => $d,
        "m" => $m,
        "Y" => $y,
        "image_api" => $image_api,
        //"timestamp" => $res['_timestamp'],
        "map" => $mapOptions,
        "adamlogo" => true,
        "analytics" => $analytics,
        "template" => "home.twig"
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

    //$volgende = "/".str_replace('-', '/', $res['_nextDate']);
    //$vorige   = "/".str_replace('-', '/', $res['_prevDate']);

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
        //"volgende" => $volgende,
        //"vorige" => $vorige,
        "datestring" => "{$y}-{$m}-{$d}",
        "dag" => $dag,
        "j" => date('j'),
        "d" => $d,
        "m" => $m,
        "Y" => $y,
        "image_api" => $image_api,
        //"timestamp" => $res['_timestamp'],
        "map" => $mapOptions,
        "analytics" => $analytics,
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

    $data = [
        "bericht" => $bericht,
        "analytics" => $analytics,
        "template" => "bericht.twig",
    ];

    render($data["template"], $data);
});

/**
 * Mail lijst csv
 */
$app->get('/mail/csv', function () use ($app) {
    if (empty($_SESSION['auth_token'])) {
        $app->redirect('/dashboard/login');
    }

    /**
     * @var ApiResponse $apiResponse
     */
    $apiResponse = $app->api->get('mail?token=' . $_SESSION['auth_token']);

    if (200 !== $apiResponse->statusCode) {
        $app->redirect('/dashboard/login');
    }

    $out = fopen('php://output', 'w');

    fputcsv($out, ['id', 'mail','naam', 'aangemaakt', 'bevestigd', 'verwijdering aangevraagd']);

    header("Content-type: text/csv");
    header("Content-Disposition: attachment; filename=maillijst.csv");
    header("Pragma: no-cache");
    header("Expires: 0");

    foreach ($apiResponse->body as $row) {
        fputcsv($out, [
            $row['id'],
            $row['name'],
            $row['created']['date'],
            $row['confirmed']['date'],
            $row['unsubscribeRequest']['date']
        ]);
    }
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
 * Styleguide
 */
$app->get('/styleguide', function () use ($app) {

    $data = [
        "template" => "styleguide.twig",
    ];

    render($data["template"], $data);
});

/**
 * Offline version
 */
$app->get('/offline', function () use ($app) {

    list($Y, $m, $d) = explode("-", date("Y-m-d"));
    $dateurlstring = "{$Y}/{$m}/{$d}";

    $apiResponse = $app->api->get("berichten/{$dateurlstring}");
    $berichten = $apiResponse->body['messages'];

    $berichten = array_filter($apiResponse->body['messages'], function ($bericht) {
        return !empty($bericht['is_live']);
    });

    usort($berichten, function ($b1, $b2) {
        return $b1['important'] < $b2['important'];
    });

    $data = [
        "berichten" => $berichten,
        "lang" => $_SESSION['lang'],
        "template" => "offline.twig",
    ];

    render($data["template"], $data);
});