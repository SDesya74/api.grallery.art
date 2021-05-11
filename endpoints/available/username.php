<?php
require_once "util/responses.php";
require_once "util/Request.php";

$collector->get(
    "/available/username",
    function () {
        $args = Request::args();
        if (!isset($args->username)) return error("Missing username field");
        $username = $args->username;

        $user = R::findOne("user", "username LIKE ?", [ $username ]);
        return ok([ "available" => $user == null ]);
    }
);
