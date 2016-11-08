<?php

/***********************
 * Wachtwoord vergeten *
 ***********************/


/**
 * Wachtwoord vergeten.
 */
$app->get('/wachtwoordvergeten', function () use ($app) {

    $data = [
        "template" => "wachtwoord-vergeten.twig",
    ];

    render($data["template"], $data);

});


/**
 * Wachtwoord vergeten post.
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
 * Wachtwoord instellen.
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
