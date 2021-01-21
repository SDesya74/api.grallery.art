<?php

class Tokenizer {
    private $secret;

    public function __construct(string $secret) {
        $this->secret = $secret;
    }

    public function generateToken($payload, $ttl): ArrayObject {  /* ttl - time to live */
        $expires = ((new DateTime())->add(new DateInterval($ttl)))->getTimestamp();

        // add exp property to payload for JWT validation
        $payload["exp"] = $expires;

        // encoding to base64 just to look prettier
        $token = JWT::urlsafeB64Encode(JWT::encode($payload, $this->secret));

        return new ArrayObject([ "token" => $token, "expires" => $expires ], ArrayObject::ARRAY_AS_PROPS);
    }

    public function decodeToken($token): ArrayObject {
        try {
            $payload = JWT::decode(JWT::urlsafeB64Decode($token), $this->secret);
            $valid = true;
        } catch (Exception $ex) {
            $payload = null;
            $valid = false;
        } finally {
            return new ArrayObject(
                [
                    "valid" => $valid,
                    "payload" => $payload
                ],
                ArrayObject::ARRAY_AS_PROPS);
        }
    }
}