<?php
require_once "util/Request.php";

if (!isset($collector)) return;

$collector->get(
    "/me",
    function() {
        $tokenizer = new Tokenizer(ACCESS_SECRET);
        [ "valid" => $valid, "payload" => $payload ] = $tokenizer->decodeToken(Request::accessToken());
        if (!$valid) return unauthorized("Invalid token");

        $user_bean = R::findOne("user", "id = ?", [ $payload->id ]);
        if ($user_bean == null) return error("User not found");

        $fields = Request::fields("user");
        return ok($user_bean->getFields($fields));
    }
);