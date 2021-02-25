<?php

require_once "AccessRights.php";
require_once "BinaryTokenizer.php";
require_once "AccessTokenPayload.php";

class AccessToken {
    private static ?AccessTokenPayload $payload;

    static function get(): AccessTokenPayload {
        assert(self::$payload !== null, "Token not found");
        return self::$payload;
    }

    public static function exists(): bool {
        return self::$payload !== null;
    }

    static function parseRequest() {
        if (isset(Request::args()->access_token)) $token = Request::args()->access_token;

        if (empty($token)) {
            $header = Request::header("Authorization");
            if ($header === null) return;

            $token = explode(" ", $header)[1];
        }

        if (empty($token)) throw new Exception("Token not found");

        $tokenizer = new BinaryTokenizer(ACCESS_SECRET);
        $data = $tokenizer->decode($token);
        self::$payload = AccessTokenPayload::fromArray($data);
    }

    public static function create(string $userID, AccessRights $rights): ArrayObject {
        $payload = new AccessTokenPayload($userID, $rights);

        $tokenizer = new BinaryTokenizer(ACCESS_SECRET);
        return $tokenizer->encode($payload->toArray(), ACCESS_TOKEN_LIFETIME);
    }
}
