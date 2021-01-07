<?php
require_once "util/Request.php";

if (!isset($collector)) return;

$collector->get(
    "/user/{username:[a-zA-Z0-9-_]{2,30}}",
    function($username) {
        $fields = Request::fields("user");

        $user_bean = R::findOne("user", "username LIKE :username", [ ":username" => $username ]);
        if ($user_bean === null) {
            return error("User not found");
        }

        $disallowed = [ "password_hash", "created_at", "nickname" ];
        if (count($fields) > 0) {
            $filtered = array_filter(
                $user_bean->jsonSerialize(),
                function($key) use ($disallowed, $fields) {
                    return !in_array($key, $disallowed) && in_array($key, $fields);
                }, ARRAY_FILTER_USE_KEY
            );
        } else {
            $filtered = array_filter(
                $user_bean->jsonSerialize(),
                function($key) use ($disallowed) {
                    return !in_array($key, $disallowed);
                }, ARRAY_FILTER_USE_KEY
            );
        }
        return ok($filtered);
    }
);