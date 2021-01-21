<?php
require_once "util/Request.php";
require_once "util/http_build_url.php";

if (!isset($collector)) return;

$collector->post(
    "/post",
    function() {
        $json = Request::getJsonFields("content");
        if (!$json->valid) return error($json->errors);
        $content = $json->payload->content;

        // TODO: Add content validation (text < 2000 chars etc.)

        $tokenizer = new Tokenizer(ACCESS_SECRET);
        ["valid" => $valid, "payload" => $payload] = $tokenizer->decodeToken(Request::accessToken());
        if (!$valid) return unauthorized("Invalid token");

        $user_bean = R::findOne("user", "username LIKE :username", [ ":username" => $payload->username ]);
        if ($user_bean === null) return error("User not found");

        $post_bean = R::dispense("post");
        $post_bean->content = json_encode($content);
        $post_bean->created = time();

        $user_bean->ownPostList[] = $post_bean;
        R::store($user_bean);

        return created(
            "Post created",
            [
                "links" => [
                    "user" => "/user/{$user_bean->username}/",
                    "post" => "/post/{$post_bean->id}/",
                ]
            ]);
    }
);

