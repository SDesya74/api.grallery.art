<?php
require_once "util/responses.php";
require_once "util/Request.php";
if (!isset($collector)) return;

$collector->post(
    "/login",
    function() {
        $json = Request::json();

        // check fields
        if (empty($json["login"])) return error("Missing login field");
        if (empty($json["password"])) return error("Missing password field");

        // login is username or email
        $login = $json["login"];
        $password = trim($json["password"]);

        // find user
        $user_bean = R::findOne("user", "username LIKE :login or email LIKE :login", [ ":login" => $login ]);
        if ($user_bean == null) return error("User with this email or username is not registered");

        // protect login spamming
        if (time() - $user_bean->last_enter < 3) { // three seconds
            return too_many_requests("Too many logins, wait 3 seconds");
        }
        $user_bean->last_enter = time();

        // verify password
        if (!password_verify($password, $user_bean->password_hash)) return error("Incorrect password");

        // create token pair
        require "variables/token.php";
        if (!isset($access_secret) || !isset($refresh_secret)) return error("Secret tokens unavailable");

        $access = (new Tokenizer($access_secret))->generateToken([ "username" => $user_bean->username ], "PT1H");
        $refresh = (new Tokenizer($refresh_secret))->generateToken([ "username" => $user_bean->username ], "P1Y");

        // create session bean
        $session_bean = R::dispense("session");
        $session_bean->refresh_token = $refresh->token;
        $session_bean->expires = $refresh->expires;

        // save refresh token to database
        $user_bean->ownSessionList[] = $session_bean;

        // save user in database
        R::store($user_bean);

        return ok([ "access" => $access, "refresh" => $refresh ]);
    }
);