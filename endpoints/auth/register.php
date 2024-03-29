<?php
require_once "util/responses.php";
require_once "util/Request.php";

$collector->post(
    "/register",
    function () {
        $json = Request::getJsonFields("username", "visible_name", "email", "password", "captcha");
        if (!$json->valid) return error($json->errors);

        [
            "username" => $username,
            "visible_name" => $visible_name,
            "email" => $email,
            "password" => $password,
            "captcha" => $captcha
        ] = $json->payload;

        // validate captcha
        $response = file_get_contents(sprintf(CAPTCHA_URL, CAPTCHA_SECRET, $captcha));
        if ($response === false) return error("Can't check CAPTCHA");

        $response = json_decode($response, true);
        if (!$response["success"]) return forbidden("You are a robot");

        // validate username
        if (!preg_match("/^[a-zA-Z0-9-_]{2,15}$/", $username)) {
            return error("Username must be between 2 and 15 characters long, contain only English letters, hyphens and underscores");
        }

        // validate visible name
        if (strlen($visible_name) < 2 || strlen($visible_name) > 30) {
            return error("Visible name must be between 2 and 30 characters long");
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

        // if login is already registered - throw error
        if (Model_User::findOneBy("username LIKE ? or email LIKE ?", [ $username, $email ]) != null) {
            return error("User with this username or email is already registered");
        }

        // add user to database
        $user = R::dispense("user");
        $user->register($username, $visible_name, $email, $password);
        R::store($user); // invalidate ID

        $session = Model_Session::createForUser($user->box());
        R::store($user);

        return ok($session, hateoas("user", $user->getLink()));
    }
);
