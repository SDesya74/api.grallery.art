<?php
// JWT::urlsafeB64Encode(JWT::encode(payload, secret))

function read_access_token_from_request() {
    $get = [];
    parse_str(parse_url($_SERVER["REQUEST_URI"], PHP_URL_QUERY), $get);
    if (isset($get["access_token"])) return $get["access_token"];

    $headers = getallheaders();
    try {
        if (isset($headers["Authorization"])) return explode(" ", $headers["Authorization"])[1];
    } catch (Exception $exception) {
        return null;
    }
    return null;
}

function generate_token_pair_for_username($username) {
    require_once "variables/token.php";
    if (!isset($access_token_secret)) return null;
    if (!isset($refresh_token_secret)) return null;

    return new ArrayObject([
        "access" => generate_token($access_token_secret, $username, "PT1H"), // 1 hour
        "refresh" => generate_token($refresh_token_secret, $username, "P1Y") // 1 year
    ], ArrayObject::ARRAY_AS_PROPS);
}

function generate_token($secret, $username, $expires) {
    $expires = (new DateTime())->add(new DateInterval($expires))->getTimestamp();
    $token = JWT::urlsafeB64Encode(JWT::encode([ "username" => $username, "exp" => $expires ], $secret));

    return new ArrayObject([
        "token" => $token,
        "expires" => $expires
    ], ArrayObject::ARRAY_AS_PROPS);
}

function decode_token_payload($token, $secret, $verify = true) {
    try {
        return JWT::decode(JWT::urlsafeB64Decode($token), $secret, $verify);
    } catch (Exception $ex) {
        return null;
    }
}

function is_token_valid($token, $secret) {
    $payload = decode_token_payload($token, $secret);
    return $payload != null;
}



