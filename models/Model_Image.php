<?php

use JetBrains\PhpStorm\ArrayShape;
use JetBrains\PhpStorm\Pure;
use RedBeanPHP\OODBBean;

class Model_Image extends RedBean_SimpleModel {
    public static function byID(string $id): ?OODBBean {
        return self::findOneBy("id LIKE ?", [ $id ]);
    }

    public static function findOneBy(string $sql, array $bindings): ?OODBBean {
        return R::findOne("image", $sql, $bindings);
    }

    public function getImageUrl(): string {
        $bean = $this->bean;
        $server = $bean->server;
        $left = $bean->left;
        $right = $bean->right;

        return "$server/$left$right";
    }

    #[Pure]
    #[ArrayShape([
        "id" => "mixed",
        "width" => "mixed",
        "height" => "mixed",
        "size" => "mixed",
        "url" => "string"
    ])]
    public function __jsonSerialize(): array {
        return [
            "id" => $this->id,
            "width" => floatval($this->width),
            "height" => floatval($this->height),
            "size" => floatval($this->size),
            "url" => $this->getImageUrl()
        ];
    }
}
