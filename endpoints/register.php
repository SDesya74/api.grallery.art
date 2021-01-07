<?php
require_once "util/responses.php";
require_once "util/Tokenizer.php";
require_once "util/Request.php";
if (!isset($collector)) return;

$collector->post(
    "/register",
    function() {
        $json = Request::json();

        // check fields
        if (empty($json->username)) return error("Missing username field");
        if (empty($json->email)) return error("Missing email field");
        if (empty($json->password)) return error("Missing password field");

        // nickname is optional field for now

        $username = $json->username;
        $nickname = isset($json->nickname) ? $json->nickname : $username;
        $email = $json->email;
        $password = trim($json->password);

        // validate username
        if (!preg_match("/^[a-zA-Z0-9-_]{2,30}$/", $username)) {
            return error("Username must be between 2 and 30 characters long, contain only English letters, hyphens and underscores");
        }

        // validate email
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return error("Invalid email");
        }

        // validate password
        if (
            strlen($password) < 8 ||
            ctype_upper($password) ||
            ctype_lower($password) ||
            !preg_match("/\d/", $password)
        ) {
            return error("Password must be at least 6 characters long, must contain at least one uppercase character, one lowercase character and one number");
        }

        // if login is registered - throw error
        if (R::findOne(
                "user",
                "username LIKE :username or email LIKE :email",
                [ ":username" => $username, ":email" => $email ]
            ) != null) {
            return error("User with this username or email is already registered");
        }

        // encode password
        $password_hash = password_hash($password, PASSWORD_DEFAULT);

        // add user to database
        $user_bean = R::dispense("user");
        $user_bean->username = $username;
        $user_bean->nickname = $nickname;
        $user_bean->email = $email;
        $user_bean->password_hash = $password_hash;
        $user_bean->last_enter = (int) time();
        $user_bean->created_at = (int) time();


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
