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
        if (!password_verify($password, $user_bean->password_hash)) return error("Incorrect password");

        $access = (new Tokenizer(ACCESS_SECRET))
            ->generateToken([ "id" => $user_bean->id ], ACCESS_TOKEN_LIFETIME);
        $refresh = (new Tokenizer(REFRESH_SECRET))
            ->generateToken([], REFRESH_TOKEN_LIFETIME);

        $user_agent = Request::header("User-Agent");

        // find or create session
        $session_bean = R::findOne("session", "user_id = ? AND user_agent = ?", [ $user_bean->id, $user_agent ]);
        if ($session_bean == null) $session_bean = R::dispense("session");

        $session_bean->refresh_token = $refresh->token;
        $session_bean->expires = $refresh->expires;
        $session_bean->user_agent = Request::header("User-Agent");

        // save refresh token to database
        $user_bean->ownSessionList[] = $session_bean;

        // save user in database
        R::store($user_bean);

        return ok(
            [ "access" => $access, "refresh" => $refresh ],
            hateoas("user", "/user/$user_bean->username")
        );
    }
);