<?php
require_once "util/Request.php";

if (!isset($collector)) return;

$collector->get(
    "/me",
    function() {
        $tokenizer = new Tokenizer(ACCESS_SECRET);
        $token = $tokenizer->decodeToken(Request::accessToken());
        if (!$token->valid) return unauthorized($token->error);

        $user_bean = R::findOne("user", "id = ?", [ $token->payload->id ]);
        if ($user_bean == null) return error("User not found");

        $fields = Request::fields("user");
        return ok($user_bean->getFields($fields));
    }
);