<?php

/********
 * Util *
 ********/


/**
 * Current token.
 */
$app->get('/token', function () use ($app) {
    die($_SESSION['auth_token']);
});


/**
 * Robots.txt
 */
$app->get('/robots.txt', function () use ($app) {
    header("Content-type: text/plain");
    if ( $_SERVER['HTTP_HOST'] == 'www.tourbuzz.nl' ) {
        readfile(__DIR__.'/../../public/robots.live.txt');
    } else {
        readfile(__DIR__.'/../../public/robots.dev.txt');
    }
    die;
});


/**
 * Translate helper
 */
$app->post('/translate', function () use ($app) {
    $translation = 'Geen vertaling mogelijk';
    $translate_url = 'translate?token='.$_SESSION["auth_token"];
    $apiResponse = $app->api->post($translate_url, [
        'lang' => $app->request->post('lang'),
        'string' => $app->request->post('string')
    ]);
    $translation = $apiResponse->body['string'];
    die($translation);
});


/**
 * Dump translations
 */
$app->get('/dump-translations', function () use ($app) {
    $translationsJson = file_get_contents("../app/translations/translations.json");

    // Fixes UTF-8 conversion issues.
    $translationsJson =  mb_convert_encoding($translationsJson, 'UTF-8', mb_detect_encoding($translationsJson, 'UTF-8, ISO-8859-1', true));

    $translations = json_decode($translationsJson);

    $data = [
        "translations" => (array)$translations->translations,
        "template" => "translations.twig"
    ];

    render($data['template'], $data);
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
 * Form boilerplate
 */
$app->get('/form-boilerplate', function () use ($app) {

    $data = [
        "template" => "form-boilerplate.twig",
    ];

    render($data["template"], $data);
});


/**
 * Form boilerplate
 */
$app->post('/form-boilerplate', function () use ($app) {

    $fields = array(
        'username' => $app->request->post('username'),
        'email' => $app->request->post('email'),
        'debug' => $app->request->post('debug'),
        'keuzes' => $app->request->post('keuzes'),
        'keuze' => $app->request->post('keuze'),
    );

    $errors = array();

    // Validate username
    if (empty($fields['username'])) {
        $errors['username'] = translate('Verplicht veld');
    }

    // Validate email
    if (empty($fields['email'])) {
        $errors['email'] = translate('Verplicht veld');
    } elseif (!filter_var($fields['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = translate('Vul een geldig email adres in');
    }

    $data = [
        "errors" => $errors,
        "form" => $fields,
        "template" => "form-boilerplate.twig",
    ];

    render($data["template"], $data);
});
