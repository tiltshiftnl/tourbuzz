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

    $apiResponse = $app->api->post("mail", $fields);

    switch ($apiResponse->statusCode) {
        case '200':
            $app->flash('success', translate('We hebben u een mail gestuurd. Klik op de link in het mailbericht.'));
            break;
        case '406':
            $app->flash('error', translate('Dit email adres is al aangemeld'));
            $app->redirect('/berichtenservice');
            break;
        case '500':
            $app->flash('error', translate('Aanmelden niet gelukt. Email is verplicht.')); // FIXME
            $app->redirect('/berichtenservice');
            break;

        default:
            $app->flash('error', 'Het is niet gelukt helaas: '.$apiResponse->statusCode);
            $app->redirect("/berichtenservice");
    }

    $app->redirect(date('/Y/m/d'));
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
