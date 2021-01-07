<?php

class Tokenizer {
    private $secret;

    public function __construct(string $secret) {
        $this->secret = $secret;
    }

    public function generateToken($payload, $ttl): ArrayObject {  /* ttl - time to live */
        $expires = (new DateTime())->add(new DateInterval($ttl))->getTimestamp();

        // add exp property to payload for JWT validation
        $data = new ArrayObject($payload);
        $data->exp = $expires;

        // encoding to base64 just to look prettier
        $token = JWT::urlsafeB64Encode(JWT::encode($data, $this->secret));

        return new ArrayObject([ "token" => $token, "expires" => $expires ], ArrayObject::ARRAY_AS_PROPS);
    }

    public function isTokenValid($token) {
        try {
            // JWT::decode throws exception on invalid tokens
            return self::decodeTokenPayload($token) !== null;
        } catch (Exception $exception) {
            return false;
        }
    }

    public function decodeTokenPayload($token) {
        return (JWT::decode(JWT::urlsafeB64Decode($token), $this->secret));
    }
}