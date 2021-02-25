<?php

require_once "AccessRights.php";

class AccessTokenPayload {
    private string $userID;
    private AccessRights $rights;

    public function __construct(string $userID, AccessRights $rights) {
        $this->userID = $userID;
        $this->rights = $rights;
    }

    public static function fromArray(array $array): AccessTokenPayload {
        assert(count($array) == 2, "Invalid array length");

        $userID = $array[0];
        $rights = AccessRights::parse($array[1]);
        return new AccessTokenPayload($userID, $rights);
    }

    public function toArray(): array {
        return [ $this->getUserID(), $this->rights->get() ];
    }

    public function getUserID(): string {
        return $this->userID;
    }
}
