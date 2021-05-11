<?php
require_once "util/responses.php";
require_once "util/Request.php";

$collector->get(
    "/available/email",
    function () {
        $args = Request::args();
        if (!isset($args->email)) return error("Missing email field");
        $email = $args->email;

        $user = R::findOne("user", "email LIKE ?", [ $email ]);
        return ok([ "available" => $user == null ]);
    }
);
