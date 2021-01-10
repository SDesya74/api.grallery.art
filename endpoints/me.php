<?php
require_once "util/Request.php";

if (!isset($collector)) return;

$collector->get(
    "/me",
    function() {
        $token = Request::accessToken();
        if ($token == null) return unauthorized("Access token not found");

        require "variables/token.php";
        if (!isset($access_secret)) return error("Access token secret unavailable");

        $token = (new Tokenizer($access_secret))->decodeToken($token);
        if (!$token->valid) return unauthorized("Invalid token");

        $user_bean = R::findOne("user", "username LIKE :username", [ ":username" => $token->payload->username ]);
        if ($user_bean === null) return error("User not found (HOW?)");

        $user_bean->id = (int) $user_bean->id;
        $user_bean->created = (int) $user_bean->created;
        $user_bean->last_enter = (int) $user_bean->last_enter;
        unset($user_bean->password_hash);
        
        return ok($user_bean);
    }
);