<?php
require_once "util/responses.php";
require_once "util/Request.php";

if (!isset($collector)) return;

$collector->post(
    "/login",
    function() {
        $json = Request::getJsonFields("login", "password");
        if (!$json->valid) return error($json->errors);

        [ "login" => $login, "password" => $password ] = $json->payload;

        // find user
        $user_bean = R::findOne("user", "username LIKE :login or email LIKE :login", [ ":login" => $login ]);
        if ($user_bean == null) return error("User with this email or username is not registered");

        // protect login spamming
        if (time() - $user_bean->last_enter < 3) { // three seconds
            return too_many_requests("Too many logins, wait 3 seconds");
        }
        $user_bean->last_enter = time();

        // verify password
        if (!$user_bean->verifyPassword($password)) return error("Incorrect password");

        $session = $user_bean->createSession();

        // save user in database
        R::store($user_bean);

        return ok($session, hateoas("user", "/user/$user_bean->username"));
    }
);