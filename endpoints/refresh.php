<?php
require_once "util/responses.php";
require_once "util/Request.php";
if (!isset($collector)) return;

$collector->post(
    "/refresh",
    function() {
        $json = Request::json();

        if (empty($json->refresh_token)) return error("Missing refresh_token field");
        $refresh_token = $json->refresh_token;

        require "variables/token.php";
        if(!isset($refresh_secret)) return error("Resresh secret unavailable");

        // validate token
        $tokenizer = new Tokenizer($refresh_secret);
        if(!$tokenizer->isTokenValid($refresh_token)) {
            return error("Refresh token invalid");
        }

        // find token
        $session_bean = R::findOne("session", "refresh_token LIKE :refresh_token", [ ":refresh_token" => $refresh_token ]);
        if ($session_bean == null) return error("Session not found");

        // create token pair
        require "variables/token.php";
        if (!isset($access_secret) || !isset($refresh_secret)) return error("Secret tokens unavailable");

        $user_bean = $session_bean->user;
        $user_bean->last_enter = time();
        $access = (new Tokenizer($access_secret))->generateToken([ "username" => $user_bean->username ], "PT1H");
        $refresh = (new Tokenizer($refresh_secret))->generateToken([ "username" => $user_bean->username ], "P1Y");

        // update token in database
        $session_bean->refresh_token = $refresh->token;
        $session_bean->expires = $refresh->expires;

        // save user in database
        R::store($user_bean);
        R::store($session_bean);

        return ok([ "access" => $access, "refresh" => $refresh ]);
    }
);