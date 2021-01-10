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

function error($message = "Internal error") {
    return response(400, [ "message" => $message ]);
}

function unauthorized($message = "Unauthorized") {
    return response(401, [ "message" => $message ]);
}

function too_many_requests($message = "Too Many Requests") {
    return response(429, [ "message" => $message ]);
}

function response($code, $data, $meta = []) {
    $status = $code < 400 ? "ok" : "error";
    $response = array_merge([ "status" => $status, "payload" => $data ], $meta);
    return [ $code, json_encode($response) ];
}
