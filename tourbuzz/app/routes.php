<?php 
   
$apiRoot = "http://api.qcommerz.nl/";
 
 
/**
 * Before
 */
$app->hook('slim.before.dispatch', function() use ($app) { 
    session_start();
    if (empty($_SESSION['lang'])) {
        $_SESSION['lang'] = 'nl';
    }
    $lang = $app->request()->params('lang');
    if (isset($lang) && in_array($lang, array('nl', 'fr', 'en'))) {
        $_SESSION['lang'] = $lang;
    }
    
});


/**
 * Home
 */
$app->get('/', function () use ($apiRoot) {
    
    $data = [
        "test" => "world",       
        "template" => "home.twig",
    ];
    render($data['template'], $data);
})->name("home");