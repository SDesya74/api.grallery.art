<?php
require_once "util/Request.php";
require_once "util/http_build_url.php";

if (!isset($collector)) return;

$collector->post(
    "/post",
    function() {
        $json = Request::json();

        // TODO: Add content validation (text < 2000 chars etc.)
        if (empty($json->content)) return error("Missing content field");

        $token = Request::accessToken();
        if ($token == null) return unauthorized("Access token not found");

        require "variables/token.php";
        if (!isset($access_secret)) return error("Access token secret unavailable");

        $token = (new Tokenizer($access_secret))->decodeToken($token);
        if (!$token->valid) return unauthorized("Invalid token");

        $user_bean = R::findOne("user", "username LIKE :username", [ ":username" => $token->payload->username ]);
        if ($user_bean === null) return error("User not found");

        $post_bean = R::dispense("post");
        $post_bean->content = json_encode($json->content);
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


