<?php

use JetBrains\PhpStorm\Pure;

class AccessRights {
    // Add some access rights

    #[Pure] public static function parse(int $data = -1): AccessRights {
        return new AccessRights();
    }

    public function get(): int {
        return -1;
    }
}