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

        $user_bean->id = (int) $user_bean->id;
        $user_bean->created = (int) $user_bean->created;
        $user_bean->last_enter = (int) $user_bean->last_enter;
        unset($user_bean->password_hash);

        return ok($user_bean);
    }
);