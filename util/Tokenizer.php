<?php

class Tokenizer {
    private $secret;

    public function __construct(string $secret) {
        $this->secret = $secret;
    }

    public static function verify($msg, $signature, $key): bool {
        return $signature === hash_hmac("sha256", $msg, $key, true);
    }

    public function generateToken($payload, $ttl): ArrayObject {  /* ttl - time to live */
        $expires = ((new DateTime())->add(new DateInterval($ttl)))->getTimestamp();
        $payload["exp"] = $expires;

        $body = self::safeBase64Encode(json_encode($payload));
        $signature = self::safeBase64Encode(hash_hmac("sha256", $body, $this->secret, true));
        $token = implode(".", [ $body, $signature ]);

        return new ArrayObject([ "token" => $token, "expires" => $expires ], ArrayObject::ARRAY_AS_PROPS);
    }

    private static function safeBase64Encode($input) {
        return str_replace("=", "", strtr(base64_encode($input), "+/", "-_"));
    }

    public function decodeToken($token): ArrayObject {
        try {
            $segments = explode(".", $token);
            if (count($segments) != 2) {
                $count = count($segments);
                throw new UnexpectedValueException("Wrong number of segments ($count)");
            }
            [ $body, $sign ] = $segments;
            if (null === $payload = json_decode(self::safeBase64Decode($body))) {
                throw new UnexpectedValueException("Invalid segment encoding");
            }
            if (self::safeBase64Decode($sign) !== hash_hmac("sha256", $body, $this->secret, true)) {
                throw new UnexpectedValueException("Signature verification failed");
            }
            if (isset($payload->exp) && time() >= $payload->exp) {
                throw new UnexpectedValueException("Expired Token");
            }

            $valid = true;
            return new ArrayObject([ "valid" => $valid, "payload" => $payload ], ArrayObject::ARRAY_AS_PROPS);
        } catch (Exception $ex) {
            $payload = null;
            $valid = false;
            $error = $ex->getMessage();
            return new ArrayObject([
                                       "valid" => $valid,
                                       "payload" => $payload,
                                       "error" => $error,
                                   ],
                                   ArrayObject::ARRAY_AS_PROPS);
        }
    }

    private static function safeBase64Decode($input) {
        $remainder = strlen($input) % 4;
        if ($remainder) {
            $padlen = 4 - $remainder;
            $input .= str_repeat("=", $padlen);
        }
        return base64_decode(strtr($input, "-_", "+/"));
    }

}