<?php
require_once "util/Request.php";

$collector->post(
    "/post",
    function () {
        $json = Request::getJsonFields("content");
        if (!$json->valid) return error($json->errors);
        $content = $json->payload->content;

        // TODO: Add content validation (text < 2000 chars etc.)

        $token = AccessToken::get();
        $user_bean = R::findOne("user", "id LIKE ?", [ $token->getUserID() ]);
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
    },
    [ "before" => "auth" ]
);

