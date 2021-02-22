<?php
require_once "util/Request.php";
require_once "util/FieldFilter.php";

if (!isset($collector)) return;

$collector->get(
    "/search/user",
    function() {
        $query = Request::args()["q"];
        if (strlen($query) == 0) return ok([ "users" => [] ]);

        [ $limit, $offset ] = Request::page();

        $total = R::count("user", "username LIKE :username", [ ":username" => "%$query%" ]);
        $users = R::findAll(
            "user",
            "username LIKE :username LIMIT :offset, :limit",
            [ ":username" => "%$query%", ":offset" => $offset, ":limit" => $limit ]);


        $fields = Request::fields("user");
        $result = [];
        foreach ($users as $bean) $result[] = $bean->getFields($fields);

        return ok(
            [ "users" => $result ],
            pagination($limit, $offset, $total)
        );
    }
);