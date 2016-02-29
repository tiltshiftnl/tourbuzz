<?php
    
/**
 * Whitelisting.
 */
$app->hook('slim.before.dispatch', function () use ($app) {
	$ips = [
		'/^84\.241\.210\.169$/', // IP whitelist voorbeeld
		'/^127\.0\.0\.1$/',
	];
	$matches = array_filter($ips, function ($ip) {
		return preg_match($ip, $_SERVER["REMOTE_ADDR"]);
	});
	/*if (!$matches) {
		$data = [
			"ip" => $_SERVER["REMOTE_ADDR"]
		];
		render("whitelist.twig", $data);
		$app->stop();
	}*/
});

/**
 * Custom 404 pagina.
 */
$app->notFound(function () use ($app) {
    $app->render("404.twig");
});

/**
 * Render wrapper om debug info te genereren.
 * FIXME Is er een andere manier om dit te doen?
 */
function render($template, $data = []) {
    global $app;
    $data['current'] = $app->router()->getCurrentRoute()->getName();

    if (isset($_GET['grid'])) {
        $gridcols = $_GET['grid'];
        if ($gridcols > 0) {
            $data['grid'] = $gridcols;        
        } else {
            $data['grid'] = 12; // default
        }
    } 

    if (isset($_GET['sessiondestroy'])) {
        
        // Unset all of the session variables.
        $_SESSION = array();
        
        // If it's desired to kill the session, also delete the session cookie.
        // Note: This will destroy the session, and not just the session data!
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        
        // Finally, destroy the session.
        session_destroy();        
        die('Session destroyed<br><a href="/">home</a>');
    } 
    
    if (isset($_GET['debug'])) {
        switch ($_GET['debug']) {
            case 'json':
                header('Content-type: application/json');
                echo json_encode($data);
                die;
            default:
                echo "<pre>";
                echo htmlspecialchars(print_r($data, true));
                echo "</pre>";
                die;
        }
    }
    $app->render($template, $data);
}

/**
 * Afbeeldingen schalen via externe url.
 */
$app->get('/image/:operation/:width/:height', function ($operation, $width, $height) use ($app) {
   
    //die('deze local?');

    $imageManager = new \Intervention\Image\ImageManager([
        "driver" => "imagick"
    ]);
    if (!in_array($operation, ['resize', 'fit'])) {
        die;
    }

    $src = $app->request->get('src');

    $width = (int) $width;
    if ($width < 1) $width = null;
    $height = (int) $height;
    if ($height < 1) $height = null;

    $image = $imageManager->make($src)->{$operation}($width, $height);
    echo $image->response('jpg', 85);
    die;
});

/**
 * Afbeeldingen schalen.
 */
$app->get('/image/:operation/:width/:height/:path+', function ($operation, $width, $height, $filepath) {
    
    
    $imageManager = new \Intervention\Image\ImageManager([
        "driver" => "imagick"
    ]);
    if (!in_array($operation, ['resize', 'fit'])) {
        die;
    }

    $filepath = implode("/", $filepath);
    if (!file_exists($filepath)) {
        die;
    }
    $width = (int) $width;
    if ($width < 1) $width = null;
    $height = (int) $height;
    if ($height < 1) $height = null;
    $image = $imageManager->make($filepath)->{$operation}($width, $height);

    echo $image->response('jpg', 85);
    die;
});

/**
 * Scss compileren.
 */
$app->get('/css/:path+', function ($filepath) {
    $filepath = implode("/", $filepath);
    if (!file_exists($filepath)) {
        die;
    }
    $scssCompiler = new scssc();
    $scssCompiler->setFormatter("scss_formatter");
    
    header("Content-type: text/css");
    echo $scssCompiler->compile("@import '{$filepath}'");
    die;
})->name("scss");

