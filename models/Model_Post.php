<?php

use RedBeanPHP\OODBBean;

class Model_Post extends RedBean_SimpleModel {
    public static function byID(string $id): ?OODBBean {
        return self::findOneBy("id LIKE ?", [ $id ]);
    }

    public static function findOneBy(string $sql, array $bindings): ?OODBBean {
        return R::findOne("post", $sql, $bindings);
    }
}
