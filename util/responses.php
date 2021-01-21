<?php

// 200 - ok
// 201 - created
// 204 - no content

// 400 - bad request
// 401 - unauthorized
// 403 - forbidden
// 404 - not found
// 405 - method not allowed
// 429 - too many requests

function ok($payload = null, $meta = []) {
    return response($payload == null && $meta == [] ? 204 : 200, $payload, $meta);
}

function created($message = "Created", $meta = []) {
    return response(201, [ "message" => $message ], $meta);
}

function error(/*$code, */ $message/*, $description*/) {
    return response(400, [ /* "code" => $code, */ "message" => $message /*, "description" => $description */ ]);
}

function unauthorized($message = "Unauthorized") {
    return response(401, [ "message" => $message ]);
}

function forbidden($message = "Forbidden") {
    return response(403, [ "message" => $message ]);
}

function too_many_requests($message = "Too Many Requests") {
    return response(429, [ "message" => $message ]);
}

function hateoas($name, $link, $params = []) {
    $params = empty($params) ? [] : [ "query" => http_build_query($params) ];
    return [
        "type" => "link",
        "name" => $name,
        "link" => urldecode(
            http_build_url(
                $link,
                $params,
                HTTP_URL_STRIP_AUTH | HTTP_URL_JOIN_PATH | HTTP_URL_JOIN_QUERY | HTTP_URL_STRIP_FRAGMENT
            )
        )
    ];
}

function pagination($limit, $offset, $total) {
    $links = [];

    if ($offset + $limit < $total) $links[] = hateoas(
        $_SERVER["REQUEST_URI"],
        "next",
        [ "page" => [ "offset" => $offset + $limit ] ]
    );
    if ($offset >= $limit) $links[] = hateoas(
        $_SERVER["REQUEST_URI"],
        "prev",
        [ "page" => [ "offset" => $offset - $limit ] ]
    );

    return $links;
}

function response($code, $data, $meta = []) {
    $status = $code < 400 ? "ok" : "error";

    $meta = [ $meta ];
    while (!empty($meta) && array_filter($meta,
            function($e) {
                return is_array($e) && empty($e["type"]);
            }) === $meta) {
        $meta = array_merge(...$meta);
    }


    $meta_response = [];
    foreach ($meta as $item) {
        if ($item["type"] == "link") {
            if (!isset($meta_response["links"])) $meta_response["links"] = [];
            $meta_response["links"][$item["name"]] = $item["link"];
        }
    }
    $response = [ "status" => $status, "payload" => $data ];
    if (!empty($meta_response)) $response["meta"] = $meta_response;
    return [ $code, json_encode($response) ];
}
