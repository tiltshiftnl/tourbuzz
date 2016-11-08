<?php

/**************
 * SMS alerts *
 **************/


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


/**
 * Redirect link vanuit SMS alert
 */
$app->get('/s/:id', function ($id) use ($app) {
    $bericht = base_convert($id, 36, 10);
    $app->redirect('/bericht/'.$bericht);
});