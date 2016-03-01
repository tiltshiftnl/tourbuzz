<?php 
   
require_once("config/config.php");
$localConfigFilePath = __DIR__ . "/config/config_local.php";
if (file_exists($localConfigFilePath)) {
	require_once($localConfigFilePath);
}

use GuzzleHttp\Client;
use GuzzleHttp\Message\Request;
use GuzzleHttp\Message\Response;

//FIXME move this class to own file.
class ApiClient {
    private $_guzzle;
    private $_apiRoot;
    
    public function __construct($url) {
        $this->_guzzle = new \GuzzleHttp\Client();
        $this->_apiRoot = $url;
    }
    
    public function getApiRoot() {
        return $this->_apiRoot;
    }
    
    public function get($uri) {
        $res = $this->_guzzle->request('GET', $this->_apiRoot . "{$uri}/");
        return json_decode($res->getBody(), true);
    }
        
    public function post($uri, $fields) {
        //FIXME use Guzzle here!
    
        //url-ify the data for the POST
        $fields_string = '';
        foreach($fields as $key=>$value) { $fields_string .= $key.'='.$value.'&'; }
        $fields_string = rtrim($fields_string, '&');
    
        //open connection
        $ch = curl_init();
        
        //set the url, number of POST vars, POST data
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);        
        curl_setopt($ch,CURLOPT_URL, $this->_apiRoot . "{$uri}/");
        curl_setopt($ch,CURLOPT_POST, count($fields));
        curl_setopt($ch,CURLOPT_POSTFIELDS, $fields_string);
        
        //execute post
        $result = curl_exec($ch);
        $message = json_decode($result);

        //close connection
        curl_close($ch);
    }
    
    public function delete($uri, $ids) {
        $ids = $ids ? $ids : [];
        
        //url-ify the data for the POST
        $fields_string = '';
        foreach($ids as $id) { $fields_string .= 'ids[]='.$id.'&'; }
        $fields_string = rtrim($fields_string, '&');

        $ch = curl_init();
        
        //set the url, number of POST vars, POST data
        curl_setopt($ch,CURLOPT_URL, $this->_apiRoot . "{$uri}/" . "?" . $fields_string);
        curl_setopt($ch,CURLOPT_CUSTOMREQUEST, "DELETE");
        
        //execute post
        $result = curl_exec($ch);
        
        //close connection
        curl_close($ch);
    }
}

$app->container->set('apiClient', new ApiClient($apiRoot));
 
/**
 * Before
 */
$app->hook('slim.before', function() use ($app) { 
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
$app->get('/', function () use ($app, $apiRoot) {
    
    if ( !empty($_SESSION['firstvisit']) ) {
        //$app->redirect(date('/Y/m/d'));
    }
    
    $_SESSION['firstvisit'] = true;
    
    $data = [
        "redirect" => date('/Y/m/d'),
        "template" => "splash.twig",
    ];
    
    render($data['template'], $data); 
});


/*****************
/* Admin Routes
 ****************/
 
/**
 * Login
 */ 
$app->get('/dashboard/login', function () use ($apiRoot) {
    
    $data = [
        "template" => "dashboard/login.twig",
    ];
    render($data['template'], $data);
})->name("login");


/**
 * Berichten get
 */ 
$app->get('/dashboard/berichten', function () use ($app, $image_api) {  
    
    $berichten = $app->container->get('apiClient')->get("berichten/");
    
    $data = [
        "berichten" => $berichten['messages'], 
        "image_api" => $image_api,
        "api" => $app->container->get('apiClient')->getApiRoot(),
        "template" => "dashboard/berichten.twig",
    ];
    
    render($data['template'], $data);
})->name("berichten");

/**
 * Berichten post
 */ 
$app->post('/dashboard/berichten', function () use ($app, $image_api) {
    
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
      	'id' => $app->request->post('id'),
        'link' => $app->request->post('link'),
        'image_url' => $app->request->post('image_url'),
    );
        
    if ( empty ($fields['title']) ) {
        $app->flashNow('error', 'Titel is niet ingevuld');
    
        $berichten = $app->container->get('apiClient')->get("berichten/");
        
        $data = [
            "berichten" => $berichten['messages'], 
            "bericht" => $fields,
            "image_api" => $image_api,     
            "api" => $app->container->get('apiClient')->getApiRoot(),
            "template" => "dashboard/berichten.twig",
        ];
        
        render($data['template'], $data);

    } else {
    
        $app->container->get('apiClient')->post("berichten/", $fields);
        
        $app->flash('success', 'Bericht toegevoegd');    
        $app->redirect("/dashboard/berichten");    
        
    }
        
})->name("berichten");


/**
 * Berichten bewerken
 */ 
$app->get('/dashboard/berichten/:id', function ($id) use ($app, $image_api) {
    
    $berichten = $app->container->get('apiClient')->get("berichten/");
    
    $data = [
        "test" => "world",
        "bericht" => $berichten['messages'][$id],
        "berichten" => $berichten['messages'],
        "image_api" => $image_api,   
        "api" => $app->container->get('apiClient')->getApiRoot(),
        "template" => "dashboard/berichten.twig",
    ];

    render($data['template'], $data);
});


/**
 * Berichten verwijderen
 */ 
$app->post('/dashboard/berichten/verwijderen', function () use ($app) {

    $ids = $app->request->post('ids');
    $berichten = $app->container->get('apiClient')->delete("berichten/", $ids);
    
    $app->flash('success', 'Bericht(en) verwijderd');        
    $app->redirect("/dashboard/berichten");
    
});

/**
 * Dag
 */
$app->get('/:y/:m/:d', function ($y, $m, $d) use ($app, $analytics, $image_api) {
    
    $berichten = $app->container->get('apiClient')->get("berichten/{$y}/{$m}/{$d}");
        
    $volgende = "/".str_replace('-', '/', $berichten['_nextDate']);
    $vorige   = "/".str_replace('-', '/', $berichten['_prevDate']);
    
    $cruisekalender = $app->container->get('apiClient')->get("cruisekalender/{$y}/{$m}/{$d}");

    $N = date('N', strtotime("{$y}-{$m}-{$d}"));
    
    $day = array (
        'ma', 'di', 'wo', 'do', 'vr', 'za', 'zo'
    );
    
    $dag = translate($day[(int)$N - 1]);    

    $data = [
        "lang" => $_SESSION['lang'],
        "berichten" => $berichten['messages'],
        "volgende" => $volgende,
        "vorige" => $vorige,
        "datestring" => "{$y}-{$m}-{$d}",
        "dag" => $dag,
        "d" => $d,
        "m" => $m,
        "y" => $y,
        "api" => $app->container->get('apiClient')->getApiRoot(),
        "image_api" => $image_api,        
        "analytics" => $analytics,
        "cruisekalender" => $cruisekalender['items'],
        //"werkzaamheden" => $wegwerkzaamheden['werkzaamheden'],
        //"evenementen" => $evenementen['evenementen'],            
        "template" => "home.twig",
    ];
    render($data['template'], $data);
})->name("home");

