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

use JetBrains\PhpStorm\ArrayShape;
use JetBrains\PhpStorm\Pure;

function ok($payload = null, $meta = []): array {
    return response($payload === null && $meta == [] ? 204 : 200, $payload, $meta);
}

function created($message = "Created", $meta = []): array {
    return response(201, [ "message" => $message ], $meta);
}

function not_found($message = "Not Found"): array {
    return response(404, [ "message" => $message ]);
}

function error(/*$code, */ $message/*, $description*/): array {
    return response(400, [ /* "code" => $code, */ "message" => $message /*, "description" => $description */ ]);
}

function unauthorized($message = "Unauthorized"): array {
    return response(401, [ "message" => $message ]);
}

function forbidden($message = "Forbidden"): array {
    return response(403, [ "message" => $message ]);
}

function too_many_requests($message = "Too Many Requests"): array {
    return response(429, [ "message" => $message ]);
}

#[Pure]
#[ArrayShape([ "type" => "string", "name" => "", "link" => "string" ])]
function hateoas($name, $link, $params = []): array {
    $parsed = parse_url($link);
    if(!empty($params)) $parsed["query"] = http_build_query($params);

    return [
        "type" => "link",
        "name" => $name,
        "link" => urldecode(build_url($parsed))
    ];
}

function pagination($limit, $offset, $total): array {
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

function response($code, $data, $meta = []): array {
    $ok = $code >= 200 && $code < 300;

    $meta = [ $meta ];
    while (!empty($meta) && array_filter($meta,
            function ($e) {
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

    $response = [ "ok" => $ok, "payload" => $data ];
    if (!empty($meta_response)) $response["meta"] = $meta_response;
    return [ $code, json_encode($response) ];
}
