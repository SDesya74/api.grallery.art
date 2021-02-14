<?php
require_once "util/Request.php";

if (!isset($collector)) return;

$collector->get(
    "/user/{username:[a-zA-Z0-9-_]{2,30}}",
    function($username) {

        $user_bean = R::findOne("user", "username LIKE :username", [ ":username" => $username ]);
        if ($user_bean === null) return error("User not found");

        $fields = Request::fields("user");
        return ok($user_bean->getFields($fields), hateoas("posts", "/user/$username/posts"));
    }
);