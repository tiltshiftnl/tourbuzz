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
    if (isset($lang) && in_array($lang, array('nl', 'fr','en'))) {
        $_SESSION['lang'] = $lang;
    }
    
});


/**
 * Home
 */
$app->get('/', function () use ($apiRoot) {
    
    $json = @file_get_contents($apiRoot . 'attracties/');
        
    if ( !empty($json) ) {
        $bestemmingen = json_decode($json, true);
    } else {
        die('Geen JSON');
    } 
    
    $data = [
        //"haltes" => $haltes['haltes'],
        //"parkeerplaatsen" => $parkeerplaatsen['parkeerplaatsen'],
        "bestemmingen" => $bestemmingen['attracties'],       
        "template" => "bestemmingen.twig",
    ];
    render($data['template'], $data);
})->name("bestemmingen");


/**
 * Bestemmingen
 */

$app->get('/bestemmingen', function() use ($apiRoot) {
    
    $json = @file_get_contents($apiRoot . 'attracties/');
        
    if ( !empty($json) ) {
        $bestemmingen = json_decode($json, true);
    } else {
        die('Geen JSON');
    } 
    
    $data = [
        //"haltes" => $haltes['haltes'],
        //"parkeerplaatsen" => $parkeerplaatsen['parkeerplaatsen'],
        "bestemmingen" => $bestemmingen['attracties'],       
        "template" => "bestemmingen.twig",
    ];
    render($data['template'], $data);
})->name("bestemmingen");


/**
 * Bestemming
 */
$app->get('/bestemmingen/:trcid', function ($trcid) use ($apiRoot) {

    $json = @file_get_contents($apiRoot . 'haltes/'); // 37.97.150.147
        
    if ( !empty($json) ) {
        $haltes = json_decode($json, true);
    } else {
        die('Geen JSON');
    }

    $json = @file_get_contents($apiRoot . 'attracties/'.$trcid); // 37.97.150.147
        
    if ( !empty($json) ) {
        $record = json_decode($json, true);
    } else {
        die('Geen JSON');
    }
            
    foreach ($haltes['haltes'] as &$halte) {
        $lat1 = $record['attractie']['coordinaten']['lat'];
        $lng1 = $record['attractie']['coordinaten']['lng'];

        $lat2 = $halte['coordinaten']['lat'];
        $lng2 = $halte['coordinaten']['lng'];

        $afstand = halteAfstand($lat1, $lng1, $lat2, $lng2);
        $halte['afstand'] = $afstand;
    } 
    
    // Sorteer op afstand
    
    
    // Sort and print the resulting array
    uasort($haltes['haltes'], 'cmpdistance');    
    
    $data = [
        "record" => $record['attractie'],
        "haltes" => $haltes['haltes'],
        //"parkeerplaatsen" => $parkeerplaatsen['parkeerplaatsen'],
        //"attracties" => $attracties['attracties'],       
        "template" => "bestemming.twig",
    ];
    render($data['template'], $data);
})->name("haltes");

/**
 * Map
 */
/*$app->get('/map', function () use ($apiRoot) {
    
    $json = @file_get_contents($apiRoot . 'attracties/');
        
    if ( !empty($json) ) {
        $bestemmingen = json_decode($json, true);
    } else {
        die('Geen JSON');
    } 
    
    $data = [
        //"haltes" => $haltes['haltes'],
        //"parkeerplaatsen" => $parkeerplaatsen['parkeerplaatsen'],
        "bestemmingen" => $bestemmingen['attracties'],       
        "template" => "bestemmingen.twig",
    ];
    render($data['template'], $data);
})->name("bestemmingen");*/

function halteAfstand($lat1, $lng1, $lat2, $lng2 ) {
    
    global $apiRoot;
    
    $json = @file_get_contents($apiRoot . 'distance/?lat1='.$lat1.'&lng1='.$lng1.'&lat2='.$lat2.'&lng2='.$lng2);

    if ( !empty($json) ) {
        $afstand = json_decode($json, true);
        $afstand = round($afstand['distance'] * 1000);
    } else {
        die('Afstand kan niet worden berekend');
    }
    return $afstand;     
}

// Comparison function
function cmpdistance($a, $b) {
    if ($a['afstand'] == $b['afstand']) {
        return 0;
    }
    return ($a['afstand'] < $b['afstand']) ? -1 : 1;
}


/**
 * Haltes
 */
$app->get('/haltes/:slug', function ($slug) use ($apiRoot) {

    $json = @file_get_contents($apiRoot . 'haltes/'); // 37.97.150.147
        
    if ( !empty($json) ) {
        $haltes = json_decode($json, true);
    } else {
        die('Geen JSON');
    }

    $json = @file_get_contents($apiRoot . 'attracties/'); // 37.97.150.147
        
    if ( !empty($json) ) {
        $attracties = json_decode($json, true);
    } else {
        die('Geen JSON');
    }
    
    $haltenummer = strtoupper($slug);

    $json = @file_get_contents($apiRoot . 'haltes/'.$haltenummer); // 37.97.150.147
    
    if ( !empty($json) ) {
        $record = json_decode($json, true);
    } else {
        die('Geen JSON');
    }
    
    foreach ($haltes['haltes'] as &$halte) {
        $lat1 = $record['halte']['coordinaten']['lat'];
        $lng1 = $record['halte']['coordinaten']['lng'];
        
        $lat2 = $halte['coordinaten']['lat'];
        $lng2 = $halte['coordinaten']['lng'];
        
        $afstand = halteAfstand($lat1, $lng1, $lat2, $lng2);
        $halte['afstand'] = $afstand;
    } 
        
    
    // Sort and print the resulting array
    uasort($haltes['haltes'], 'cmpdistance');

    foreach ($attracties['attracties'] as &$attractie) {
        $lat1 = $record['halte']['coordinaten']['lat'];
        $lng1 = $record['halte']['coordinaten']['lng'];
        
        $lat2 = $attractie['coordinaten']['lat'];
        $lng2 = $attractie['coordinaten']['lng'];
        
        $afstand = halteAfstand($lat1, $lng1, $lat2, $lng2);
        $attractie['afstand'] = $afstand;
    } 
    
    // Sorteer op afstand
    
    // Sort and print the resulting array
    uasort($attracties['attracties'], 'cmpdistance');    
    
    $data = [
        "record" => $record['halte'],
        "haltes" => $haltes['haltes'],
        "bestemmingen" => $attracties['attracties'],       
        "template" => "halte.twig",
    ];
    render($data['template'], $data);
});