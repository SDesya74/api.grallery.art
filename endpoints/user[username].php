<?php
require_once "util/Request.php";

if (!isset($collector)) return;

$collector->get(
    "/user/{username:[a-zA-Z0-9-_]{2,30}}",
    function($username) {
        $fields = Request::fields("user");

        $user_bean = R::findOne("user", "username LIKE :username", [ ":username" => $username ]);
        if ($user_bean === null) return error("User not found");

        unset($user_bean->password_hash);

        if (!empty($fields)) {
            $result = array_filter(
                $user_bean->jsonSerialize(),
                function($key) use ($fields) {
                    return in_array($key, $fields);
                },
                ARRAY_FILTER_USE_KEY
            );
        }else $result = $user_bean;

        return ok($result, hateoas("posts","/user/$username/posts"));
    }
);