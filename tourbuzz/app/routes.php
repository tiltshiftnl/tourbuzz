<?php 
   
require_once("config.php"); 
 
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
    //$app->view->setData($lang);
});


/**
 * Home
 */
$app->get('/', function () use ($app, $apiRoot) {
   $app->redirect(date('/Y/m/d'));
});


/**
 * Dag
 */
$app->get('/:y/:m/:d', function ($y, $m, $d) use ($apiRoot) {

    $json = @file_get_contents($apiRoot . "berichten/{$y}/{$m}/{$d}");
          
    if ( !empty($json) ) {
        $berichten = json_decode($json, true);
    } else {
       die('Geen JSON');
    }
    
    $volgende = "/".str_replace('-', '/', $berichten['_nextDate']);
    $vorige   = "/".str_replace('-', '/', $berichten['_prevDate']);
        
    //$json = @file_get_contents($apiRoot . 'cruisekalender/'.date('Y').'/'.date('m').'/'.date('d'));    
    $json = @file_get_contents($apiRoot . "cruisekalender/{$y}/{$m}/{$d}");
          
    if ( !empty($json) ) {
        $cruisekalender = json_decode($json, true);
    } else {
        die('Geen JSON');
    }
    
    /*
    $json = @file_get_contents($apiRoot . "wegwerkzaamheden/{$y}/{$m}/{$d}");
    
    if ( !empty($json) ) {
        $wegwerkzaamheden = json_decode($json, true);
    } else {
        die('Geen JSON');
    }*/
     
   //$json = @file_get_contents($apiRoot . 'cruisekalender/'.date('Y').'/'.date('m').'/'.date('d'));    
    /*$json = @file_get_contents($apiRoot . "evenementen/{$y}/{$m}/{$d}");
       
      
    if ( !empty($json) ) {
        $evenementen = json_decode($json, true);
    } else {
        die('Geen JSON');
    } */
        
    $N = date('N', strtotime("{$y}-{$m}-{$d}"));
    
    $day = array (
        'ma', 'di', 'wo', 'do', 'vr', 'za', 'zo'
    );
    
    $dag = translate($day[(int)$N - 1]);    

    $data = [
        "test" => "world",
        "berichten" => $berichten['messages'],
        "volgende" => $volgende,
        "vorige" => $vorige,
        "datestring" => "{$y}-{$m}-{$d}",
        "dag" => $dag,
        "d" => $d,
        "m" => $m,
        "y" => $y,
        "cruisekalender" => $cruisekalender['items'],
        //"werkzaamheden" => $wegwerkzaamheden['werkzaamheden'],
        //"evenementen" => $evenementen['evenementen'],            
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
$app->post('/dashboard/berichten/', function () use ($apiRoot, $app) {

    //extract data from the post
    //set POST variables
    $url = $apiRoot . 'berichten/';
    
    $fields = array(
        'category' => $app->request->post('category'),
    	'title' => $app->request->post('title'),
    	'body' => $app->request->post('body'),
    	'title_en' => $app->request->post('title_en'),
    	'body_en' => $app->request->post('body_en'),
    	'title_fr' => $app->request->post('title_fr'),
    	'body_fr' => $app->request->post('body_fr'),    	    	
    	'startdate' => $app->request->post('startdate'),
    	'enddate' => $app->request->post('enddate'),
    );
    
    //die(print_r($fields));
    
    if ( empty ($fields['title']) ) {
        $feedback = 'Je hebt geen titel ingevuld';
    } else {
    
        //url-ify the data for the POST
        $fields_string = '';
        foreach($fields as $key=>$value) { $fields_string .= $key.'='.$value.'&'; }
        $fields_string = rtrim($fields_string, '&');
    
        //open connection
        $ch = curl_init();
        
        //set the url, number of POST vars, POST data
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);        
        curl_setopt($ch,CURLOPT_URL, $url);
        curl_setopt($ch,CURLOPT_POST, count($fields));
        curl_setopt($ch,CURLOPT_POSTFIELDS, $fields_string);
        
        //execute post
        $result = curl_exec($ch);
        
        //close connection
        curl_close($ch);
        
        $feedback = 'Bericht toegevoegd';
    }
    
    $json = @file_get_contents($apiRoot . 'berichten/');
          
    if ( !empty($json) ) {
        $berichten = json_decode($json, true);
    } else {
        die('Geen JSON');
    }
    
    $data = [
        "test" => "world",
        "feedback" => $feedback,
        "berichten" => $berichten['messages'],      
        "template" => "dashboard/berichten.twig",
    ];
    render($data['template'], $data);
})->name("berichten");

/**
 * Berichten
 */ 
$app->post('/dashboard/berichten/verwijderen', function () use ($apiRoot, $app) {

    //extract data from the post
    //set POST variables
    $url = $apiRoot . 'berichten/';
    
    //die (print_r($app->request));
    $ids = $app->request->post('ids');
    
    //url-ify the data for the POST
    $fields_string = '';
    foreach($ids as $id) { $fields_string .= 'ids[]='.$id.'&'; }
    $fields_string = rtrim($fields_string, '&');

    $ch = curl_init();
    
    //set the url, number of POST vars, POST data
    curl_setopt($ch,CURLOPT_URL, $url . "?" . $fields_string);
    curl_setopt($ch,CURLOPT_CUSTOMREQUEST, "DELETE");
    
    //execute post
    $result = curl_exec($ch);
    
    //close connection
    curl_close($ch);
        
    $app->redirect("/dashboard/berichten");
    
});