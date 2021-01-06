<?php
require_once "util/responses.php";
require_once "util/json.php";
if (!isset($collector)) return;

$collector->post(
    "/login",
    function() {
        $json = get_posted_json();

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
            return too_many_requests("Too many login requests, wait 3 seconds");
        }
        $user_bean->last_enter = time();

        // verify password
        if (!password_verify($password, $user_bean->password_hash)) return error("Incorrect password");

        // create token pair
        $tokens = generate_token_pair_for_username($user_bean->username);

        // create session bean
        $user_session_bean = R::dispense("session");
        $user_session_bean->refresh_token = $tokens->refresh->token;
        $user_session_bean->expires = $tokens->refresh->expires;

        // save refresh token to database
        $user_bean->ownSessionList[] = $user_session_bean;

        // save user in database
        R::store($user_bean);

        return ok($tokens);
    }
);