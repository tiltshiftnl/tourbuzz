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
        
    //$json = @file_get_contents($apiRoot . 'cruisekalender/'.date('Y').'/'.date('m').'/'.date('d'));    
    $json = @file_get_contents($apiRoot . 'cruisekalender/2016/02/17');
          
    if ( !empty($json) ) {
        $cruisekalender = json_decode($json, true);
    } else {
        die('Geen JSON');
    }
    
    $json = @file_get_contents($apiRoot . 'wegwerkzaamheden/2016/03/02');
    
    if ( !empty($json) ) {
        $wegwerkzaamheden = json_decode($json, true);
    } else {
        die('Geen JSON');
    }
     
   //$json = @file_get_contents($apiRoot . 'cruisekalender/'.date('Y').'/'.date('m').'/'.date('d'));    
    $json = @file_get_contents($apiRoot . 'evenementen/2016/02/17');
          
    if ( !empty($json) ) {
        $evenementen = json_decode($json, true);
    } else {
        die('Geen JSON');
    }     
        
    $data = [
        "test" => "world",
        "cruisekalender" => $cruisekalender['items'],
        "werkzaamheden" => $wegwerkzaamheden['werkzaamheden'],
        "evenementen" => $evenementen['evenementen'],            
        "template" => "home.twig",
    ];
    render($data['template'], $data);
})->name("home");


/*****************
/* Admin Routes
 ****************/
 
/**
 * Login
 */ 
$app->get('/dashboard/login', function () use ($apiRoot) {
    
    $data = [
        "test" => "world",       
        "template" => "dashboard/login.twig",
    ];
    render($data['template'], $data);
})->name("login");

/**
 * Berichten
 */ 
$app->get('/dashboard/berichten', function () use ($apiRoot) {
    
    $json = @file_get_contents($apiRoot . 'berichten/');
          
    if ( !empty($json) ) {
        $berichten = json_decode($json, true);
    } else {
        die('Geen JSON');
    }
    
    $data = [
        "test" => "world",
        "berichten" => $berichten['messages'],      
        "template" => "dashboard/berichten.twig",
    ];

    render($data['template'], $data);
})->name("berichten");

/**
 * Berichten
 */ 
$app->post('/dashboard/berichten/', function () use ($apiRoot) {

    $data = [
        "test" => "world",
        "berichten" => $berichten,      
        "template" => "dashboard/berichten.twig",
    ];
    render($data['template'], $data);
})->name("berichten");