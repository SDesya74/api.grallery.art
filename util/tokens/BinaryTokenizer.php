<?php

class BinaryTokenizer {
    private static string $hash_algo = "sha256";
    private static int $hash_length = 32;
    private static string $encrypt_algo = "aes128";
    private static int $encrypt_length = 16;

    private string $secret;

    public function __construct(string $secret) {
        $this->secret = $secret;
        if (!defined("OPEN_SSL_IV")) define("OPEN_SSL_IV", str_repeat("0", self::$encrypt_length));
    }

    public function encode(array $data, string $ttl = null): ArrayObject {
        $data = [ $data ];
        if ($ttl !== null) {
            $interval = new DateInterval($ttl);
            $now = new DateTime();
            $expires = $now->add($interval)->getTimestamp();
            $data[] = $expires;
        }

        $payload = serialize($data);
        $right = openssl_encrypt($payload, self::$encrypt_algo, $this->secret, 0, OPEN_SSL_IV);
        $left = hash_hmac(self::$hash_algo, $right, $this->secret, true);

        $token = trim(strtr(base64_encode("$left$right"), "+/", "-_"), "=");

        $result = [ "token" => $token ];
        if (isset($expires)) $result["expires"] = $expires;
        return new ArrayObject($result, ArrayObject::ARRAY_AS_PROPS);
    }

    public function decode(string $token): array {
        $remainder = strlen($token) % 3;
        if ($remainder) $token .= str_repeat("=", 3 - $remainder);
        $token = base64_decode(strtr($token, "-_", "+/"));

        $left = substr($token, 0, self::$hash_length);
        $right = substr($token, self::$hash_length);

        $hash = hash_hmac(self::$hash_algo, $right, $this->secret, true);
        if ($left !== $hash) {
            throw new Exception("Token verification failed");
        }

        $right = openssl_decrypt($right, self::$encrypt_algo, $this->secret, 0, OPEN_SSL_IV);
        $payload = unserialize($right);

        if (isset($payload[1])) {
            $expires = $payload[1];
            if (time() > $expires) throw new Exception("Expired Token");
        }

        return $payload[0];
    }

}