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

function ok($data = null) {
    return response($data == null ? 204 : 200, $data);
}

function created($data) {
    return response(201, $data);
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


function response($code, $data) {
    $status = $code < 400 ? "ok" : "error";
    return [ $code, json_encode([ "status" => $status, "payload" => $data ]) ];
}
