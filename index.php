<?php

use RedBeanPHP\OODB;
use RedBeanPHP\ToolBox;

error_reporting(E_ALL);

require_once "vendor/autoload.php";

require_once "variables/token.php";
require_once "variables/captcha.php";

require_once "util/tokens/AccessToken.php";
require_once "util/tokens/RefreshToken.php";

// region CORS
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Authorization, Content-Type");
if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    header("Access-Control-Allow-Methods: POST, GET, DELETE, PUT, PATCH, OPTIONS");
    header("Access-Control-Allow-Headers: Authorization, Content-Type");
    header("Access-Control-Max-Age: 1728000");
    header("Content-Length: 0");
    header("Content-Type: application/json");
    die();
}
// endregion

// region Connect to database with RedBean
require_once "lib/rb.php";
require_once "variables/db.php";
require_once "variables/uuid.php";
require_once "util/UUID.php";

if (isset($db_host) && isset($db_user) && isset($db_pass)) {
    R::setup($db_host, $db_user, $db_pass);

    $oldToolBox = R::getToolBox();
    $oldAdapter = $oldToolBox->getDatabaseAdapter();
    $uuidWriter = new UUIDWriterMySQL($oldAdapter);
    $newRedBean = new OODB($uuidWriter);
    $newToolBox = new ToolBox($newRedBean, $oldAdapter, $uuidWriter);
    R::configureFacadeWithToolbox($newToolBox);
}
// endregion

// region Get all models
$it = new RecursiveDirectoryIterator("models/");
foreach (new RecursiveIteratorIterator($it) as $endpoint) {
    if ($endpoint->getExtension() == "php") {
        include_once $endpoint;
    }
}
// endregion

// region Get all routes
$collector = new Phroute\RouteCollector();

$it = new RecursiveDirectoryIterator("filters/");
foreach (new RecursiveIteratorIterator($it) as $filter) {
    if ($filter->getExtension() != "php") continue;
    include_once $filter;
}

$it = new RecursiveDirectoryIterator("endpoints/");
foreach (new RecursiveIteratorIterator($it) as $endpoint) {
    if ($endpoint->getExtension() != "php") continue;
    include_once $endpoint;
}
// endregion

// region Working with request
function send_response($response) {
    $code = $response[0];
    $result = $response[1];

    http_response_code($code);
    header("Content-Type: application/json; charset=utf-8");
    echo $result;
}

$dispatcher = new Phroute\Dispatcher($collector);
try {
    $response = $dispatcher->dispatch(
        $_SERVER["REQUEST_METHOD"],
        parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH)
    );
    send_response($response);
} catch (Phroute\Exception\HttpMethodNotAllowedException $exception) {
    send_response(
        response(405, [ "message" => "Method not allowed" ])
    );
} catch (Phroute\Exception\HttpRouteNotFoundException $exception) {
    send_response(
        response(404, [ "message" => "Invalid endpoint" ])
    );
} catch (Phroute\Exception\BadRouteException $exception) {
    send_response(
        response(500, [ "message" => "Bad route" ])
    );
} catch (Exception $exception) {
    var_dump($exception);
}
// endregion

R::close();