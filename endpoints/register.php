<?php
require_once "util/responses.php";
require_once "util/json.php";
require_once "util/token.php";
if (!isset($collector)) return;

$collector->post(
    "/register",
    function() {
        $json = get_posted_json();

        // check fields
        if (empty($json["username"])) return error("Missing username field");
        if (empty($json["email"])) return error("Missing email field");
        if (empty($json["password"])) return error("Missing password field");

        $username = $json["username"];
        $email = $json["email"];
        $password = trim($json["password"]);

        // validate login
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
        $user_bean->email = $email;
        $user_bean->password_hash = $password_hash;
        $user_bean->last_enter = time();

        // create token pair
        $tokens = generate_token_pair_for_username($username);

        // create session bean
        $user_session_bean = R::dispense("session");
        $user_session_bean->refresh_token = $tokens->refresh->token;
        $user_session_bean->expires = $tokens->refresh->expires;

        // save refresh token to database
        $user_bean->ownSessionList[] = $user_session_bean;

        // save user in database
        R::store($user_bean);

        // send result
        return ok($tokens);
    }
);
