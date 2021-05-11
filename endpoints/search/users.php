<?php
require_once "util/Request.php";
require_once "util/FieldFilter.php";

$collector->get(
    "/search/users",
    function () {
        $query = Request::args()["q"];
        if (strlen($query) < 1) return ok([]);

        [ $offset, $limit ] = Request::page();

        $total = R::count("user", "username LIKE :q OR visible_name LIKE :q", [ ":q" => "%$query%" ]);
        $users = R::findAll("user",
            "username LIKE :q OR visible_name LIKE :q LIMIT :offset, :limit",
            [ ":q" => "%$query%", ":offset" => $offset, ":limit" => $limit ]
        );


        return ok(array_values($users), pagination($limit, $offset, $total));
    }
);