<?php
require_once "util/responses.php";
require_once "util/Request.php";
require_once "variables/token.php";

if (!isset($collector)) return;

$collector->post(
    "/refresh",
    function() {
        $json = Request::getJsonFields("refresh_token");
        if (!$json->valid) return error($json->errors);

        $refresh_token = $json->payload->refresh_token;
        $refresh_tokenizer = new Tokenizer(REFRESH_SECRET);

        // validate token
        [ "valid" => $valid ] = $refresh_tokenizer->decodeToken($refresh_token);
        if (!$valid) return unauthorized("Invalid token");

        // find token
        $session_bean = R::findOne("session", "refresh_token LIKE ?", [ $refresh_token ]);
        if ($session_bean == null) return error("Session not found");

        $user_bean = $session_bean->user;
        $user_bean->last_enter = time();

        $access_tokenizer = new Tokenizer(ACCESS_SECRET);
        $access = $access_tokenizer->generateToken([ "id" => $user_bean->id ], ACCESS_TOKEN_LIFETIME);
        $refresh = $refresh_tokenizer->generateToken([], REFRESH_TOKEN_LIFETIME);

        // update token in database
        $session_bean->refresh_token = $refresh->token;
        $session_bean->expires = $refresh->expires;

        // save user in database
        R::store($user_bean);
        R::store($session_bean);

        return ok(
            [ "access" => $access, "refresh" => $refresh ],
            hateoas("user", "/user/$user_bean->username")
        );
    }
);