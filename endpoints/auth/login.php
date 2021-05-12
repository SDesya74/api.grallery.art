<?php
require_once "util/responses.php";
require_once "util/Request.php";
require_once "models/Model_User.php";

$collector->post(
    "/login",
    function () {
        $json = Request::getJsonFields("login", "password");
        if (!$json->valid) return error($json->errors);

        $user_bean = Model_User::byUsernameOrEmail($json->payload->login);
        if ($user_bean == null) return error("User with this email or username is not registered");

        // protect login spamming
        if (time() - $user_bean->last_enter < 3) { // three seconds
            return too_many_requests("Too many logins, wait 3 seconds");
        }
        $user_bean->last_enter = time();

        // verify password
        if (!$user_bean->verifyPassword($json->payload->password)) return error("Incorrect password");

        $session = Model_Session::createForUser($user_bean->box());
        R::store($user_bean);

        return ok($session, hateoas("user", "/user/$user_bean->username"));
    }
);