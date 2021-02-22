<?php

use JetBrains\PhpStorm\Pure;

class Tokenizer {
    private string $secret;

    public function __construct(string $secret) {
        $this->secret = $secret;
    }

    public function generateToken($payload, $ttl): ArrayObject {  /* ttl - time to live */
        $expires = ((new DateTime())->add(new DateInterval($ttl)))->getTimestamp();
        $payload["exp"] = $expires;

        $body = self::safeBase64Encode(json_encode($payload));
        $signature = self::safeBase64Encode(hash_hmac("sha256", $body, $this->secret, true));
        $token = implode(".", [ $body, $signature ]);

        return new ArrayObject([ "token" => $token, "expires" => $expires ], ArrayObject::ARRAY_AS_PROPS);
    }

    private static function safeBase64Encode(string $input): string {
        return str_replace("=", "", strtr(base64_encode($input), "+/", "-_"));
    }

    public function decodeToken(string $token): ArrayObject {
        try {
            $segments = explode(".", $token);
            if (count($segments) != 2) {
                $count = count($segments);
                throw new UnexpectedValueException("Wrong number of segments ($count)");
            }
            [ $body, $sign ] = $segments;
            if (null === $payload = json_decode(Tokenizer::safeBase64Decode($body))) {
                throw new UnexpectedValueException("Invalid segment encoding");
            }
            if (Tokenizer::safeBase64Decode($sign) !== hash_hmac("sha256", $body, $this->secret, true)) {
                throw new UnexpectedValueException("Signature verification failed");
            }
            if (isset($payload->exp) && time() >= $payload->exp) {
                throw new UnexpectedValueException("Expired Token");
            }

            return new ArrayObject(
                [
                    "valid" => true,
                    "payload" => $payload
                ],
                ArrayObject::ARRAY_AS_PROPS
            );
        } catch (Exception $ex) {
            return new ArrayObject(
                [
                    "valid" => false,
                    "payload" => null,
                    "error" => $ex->getMessage(),
                ],
                ArrayObject::ARRAY_AS_PROPS
            );
        }
    }

    #[Pure] private static function safeBase64Decode(string $input): string {
        $remainder = strlen($input) % 4;
        if ($remainder) {
            $padding = 4 - $remainder;
            $input .= str_repeat("=", $padding);
        }
        return base64_decode(strtr($input, "-_", "+/"));
    }

}