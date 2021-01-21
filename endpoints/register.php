<?php
require_once "util/responses.php";
require_once "util/Tokenizer.php";
require_once "util/Request.php";

if (!isset($collector)) return;

$collector->post(
    "/register",
    function() {
        $json = Request::getJsonFields("username", "email", "password", "captcha");
        if (!$json->valid) return error($json->errors);

        $payload = $json->payload;
        [ "username" => $username, "email" => $email, "password" => $password, "captcha" => $captcha ] = $payload;

        // validate captcha
        $secret = CAPTCHA_SECRET;
        $response = file_get_contents("https://www.google.com/recaptcha/api/siteverify?secret=$secret&response=$captcha");
        if ($response === false) return error("Can't check CAPTCHA");

        $response = json_decode($response, true);
        if (!$response["success"]) return forbidden("You are a robot");

        // nickname is optional field for now
        $nickname = isset($payload->nickname) ? $payload->nickname : $username;

        // validate username
        if (!preg_match("/^[a-zA-Z0-9-_]{2,30}$/", $username)) {
            return error("Username must be between 2 and 30 characters long, contain only English letters, hyphens and underscores");
        }

        // validate email
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) return error("Invalid email");

        // validate password
        if (
            strlen($password) < 8 ||
            ctype_upper($password) ||
            ctype_lower($password) ||
            !preg_match("/\d/", $password)
        ) {
            return error(
                "Password must be at least 6 characters long, " .
                "must contain at least one uppercase character, " .
                "one lowercase character and one number"
            );
        }

        // if login is registered - throw error
        if (R::findOne("user", "username LIKE ? or email LIKE ?", [ $username, $email ]) != null) {
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
        $user_bean->created = (int) time();

        $access_tokenizer = new Tokenizer(ACCESS_SECRET);
        $refresh_tokenizer = new Tokenizer(REFRESH_SECRET);

        // create token pair
        $access = $access_tokenizer->generateToken([ "id" => $user_bean->id ], ACCESS_TOKEN_LIFETIME);
        $refresh = $refresh_tokenizer->generateToken([], REFRESH_TOKEN_LIFETIME);

        // create session bean
        $session_bean = R::dispense("session");
        $session_bean->refresh_token = $refresh->token;
        $session_bean->expires = $refresh->expires;
        $session_bean->user_agent = Request::header("User-Agent");

        // save refresh token to database
        $user_bean->ownSessionList[] = $session_bean;

        // save user in database
        R::store($user_bean);

        return ok([ "access" => $access, "refresh" => $refresh ], hateoas("user", "/user/$username"));
    }
);
