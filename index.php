<?php

error_reporting(E_ALL);

require_once "vendor/autoload.php";
require_once "lib/rb.php";
require_once "variables/db.php";

if (isset($db_host) && isset($db_user) && isset($db_pass)) {
    R::setup($db_host, $db_user, $db_pass);
}

$collector = new Phroute\RouteCollector();
foreach (glob("endpoints/*.php") as $endpoint) {
    include_once $endpoint;
}

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
        response(400, [ "message" => "Invalid endpoint" ])
    );
} catch (Phroute\Exception\BadRouteException $exception) {
    send_response(
        response(400, [ "message" => "Bad route" ])
    );
}

