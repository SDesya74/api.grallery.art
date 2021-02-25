<?php

class RefreshTokenPayload {
    private string $userID;

    public function __construct(string $userID) {
        $this->userID = $userID;
    }

    public static function fromArray(array $array): RefreshTokenPayload {
        assert(count($array) == 1, "Invalid array length");

        $userID = $array[0];
        return new RefreshTokenPayload($userID);
    }

    public function toArray(): array {
        return [ $this->getUserID() ];
    }

    public function getUserID(): string {
        return $this->userID;
    }
}

class RefreshToken {
    public static function create(string $userID): ArrayObject {
        $payload = new RefreshTokenPayload($userID);

        $tokenizer = new BinaryTokenizer(REFRESH_SECRET);
        return $tokenizer->encode($payload->toArray(), ACCESS_TOKEN_LIFETIME);
    }

    public static function decode(string $token): RefreshTokenPayload {
        $tokenizer = new BinaryTokenizer(REFRESH_SECRET);
        $decoded = $tokenizer->decode($token);

        return RefreshTokenPayload::fromArray($decoded);
    }
}
