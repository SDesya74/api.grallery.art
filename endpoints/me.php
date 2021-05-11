<?php
require_once "util/Request.php";

$collector->get(
    "/me",
    function () {
        $token = AccessToken::get();

        $user_bean = Model_User::byID($token->getUserID());
        if ($user_bean == null) return error("User not found");

        $username = $user_bean->username;

        $fields = Request::fields("user");
        return ok($user_bean->getFields($fields), hateoas("posts", "user/$username/posts"));
    },
    [ "before" => "auth" ]
);