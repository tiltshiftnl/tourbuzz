<?php


/**
 * Custom 404 page.
 */
$app->notFound(function () use ($app) {
    $app->render("404.twig");
});


/**
 * Render wrapper to add debug utilities.
 */
function render($template, $data = [], $headers = []) {

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

    if (isset($_GET['routes'])) {
        $routes_dump = RouteDumper::getAllRoutes();
        $routes = array();
        $i = 0;
        foreach ($routes_dump as $route) {
            $routes[$i]['pattern'] = $route->getPattern();
            $routes[$i]['name'] = $route->getName();
            $routes[$i]['methods'] = $route->getHttpMethods();
            $i++;
        }

        $data['routes'] = $routes;
    }

    if (isset($_REQUEST['debug'])) {
        switch ($_REQUEST['debug']) {
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

    // Add custom headers to response.
    foreach ($headers as $key => $value) {
        $app->response->headers->set($key, $value);
    }

    $app->render($template, $data);
}


/**
 * Scale images on demand.
 */
$app->get('/image/:operation/:width/:height', function ($operation, $width, $height) use ($app) {

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
    exit($image->response('jpg', 85));
});


/**
 * Compile scss files to css format.
 */
$app->get('/css/:path+', function ($filepath) {

    $filepath = implode("/", $filepath);
    if (!file_exists($filepath)) {
        die;
    }

    $scssCompiler = new scssc();
    $scssCompiler->setFormatter("scss_formatter");

    header("Content-type: text/css");
    exit($scssCompiler->compile("@import '{$filepath}'"));
});

