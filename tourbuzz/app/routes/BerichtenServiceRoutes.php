<?php

/********************
 * Berichtenservice *
 ********************/


/**
 * Berichtenservice.
 */
$app->get('/berichtenservice', function () use ($app) {

    $data = [
        "lang" => $_SESSION['lang'],
        "template" => "berichtenservice-aanmelden.twig",
    ];

    render($data['template'], $data);
});


/**
 * Berichtenservice aanmelden.
 */
$app->post('/berichtenservice', function () use ($app) {

    $fields = array(
        'mail' => $app->request->post('mail'),
      	'name' => $app->request->post('name'),
      	'organisation' => $app->request->post('organisation'),
      	'language' => $app->request->post('language'),
    );

    $errors = array();

    // Validate email
    if (empty($fields['mail'])) {
        $errors['mail'] = translate('Verplicht veld');
    } elseif (!filter_var($fields['mail'], FILTER_VALIDATE_EMAIL)) {
        $errors['mail'] = translate('Vul een geldig email adres in');
    }

    // Perform API call if fields pass validation
    if ( !empty($errors) ) {
        $app->flashNow('error', translate('Niet alle velden zijn goed ingevuld.'));
    } else {
        $apiResponse = $app->api->post("mail", $fields);
        switch ($apiResponse->statusCode) {
            case '200':
                $app->flash('success', translate('We hebben u een mail gestuurd. Klik op de link in het mailbericht.'));
                $app->redirect(date('/Y/m/d'));

            case '406':
                $app->flashNow('error', translate('Dit email adres is al aangemeld'));
                break;

            case '500':
                $app->flashNow('error', translate('Aanmelden niet gelukt.'));
                break;

            default:
                $app->flashNow('error', 'Het is niet gelukt helaas: '.$apiResponse->statusCode);
                break;
        }
    }

    $data = [
        "lang" => $_SESSION['lang'],
        "errors" => $errors,
        "form" => $fields,
        "template" => "berichtenservice-aanmelden.twig",
    ];

    render($data["template"], $data);

});


/**
 * Berichtenservice mail bevestigen.
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
 * Berichtenservice afmelden.
 */
$app->get('/berichtenservice-afmelden', function () use ($app) {

    $data = [
        "lang" => $_SESSION['lang'],
        "template" => "berichtenservice-afmelden.twig",
    ];

    render($data['template'], $data);
});


/**
 * Berichtenservice afmelden.
 */
$app->post('/berichtenservice-afmelden', function () use ($app) {

    $fields = array(
        'mail' => $app->request->post('mail'),
    );

    $apiResponse = $app->api->post("mail/unsubscribe", $fields);

    switch ($apiResponse->statusCode) {
        case '200':
            $app->flash('success', translate('We hebben u een mail gestuurd. Klik op de link in het mailbericht.'));
            break;
        case '406':
            $app->flash('error', translate('Dit email adres is niet bekend'));
            break;

        default:
            //die(print_r($apiResponse->body));
            $app->flash('error', 'Het is niet gelukt helaas: '.$apiResponse->statusCode);
            $app->redirect("/berichtenservice-afmelden");
    }

    $app->redirect('/berichtenservice-afmelden');
});


/**
 * Berichtenservice afmelden bevestigen.
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
 * Mail lijst csv.
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

    fputcsv($out, ['id', 'mail','naam', 'aangemaakt', 'bevestigd', 'verwijdering aangevraagd', 'organisatie']);

    header("Content-type: text/csv");
    header("Content-Disposition: attachment; filename=maillijst.csv");
    header("Pragma: no-cache");
    header("Expires: 0");

    foreach ($apiResponse->body as $row) {
        fputcsv($out, [
            $row['id'],
            $row['mail'],
            $row['name'],
            $row['created']['date'],
            $row['confirmed']['date'],
            $row['unsubscribeRequest']['date'],
            $row['organisation'],
        ]);
    }
});
