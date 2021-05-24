<?php


use RedBeanPHP\OODBBean;

class Model_Album extends RedBean_SimpleModel {
    public static function byID(string $id): ?OODBBean {
        return self::findOneBy("id LIKE ?", [ $id ]);
    }

    public static function findOneBy(string $sql, array $bindings): ?OODBBean {
        return R::findOne("album", $sql, $bindings);
    }

    public function __jsonSerialize(): array {
        $user = $this->user;

        return [
            "id" => $this->id,
            "title" => $this->title,
            "description" => $this->description,
            "author" => [
                "username" => $user->username
            ],
            "created" => $this->created,
            "preview" => $this->image,
            "elements" => array_values($this->ownAlbumelementList)
        ];
    }
}