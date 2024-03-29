<?php
require_once "util/Request.php";

$collector->get(
    "/post/{id:i}",
    function ($id) {
        $post_bean = R::findOne("post", "id = ?", [ $id ]);
        if ($post_bean == null) return error("Post not found");

        $post_bean->content = json_decode($post_bean->content);
        $post_bean->created = (int) $post_bean->created;
        $post_bean->id = (int) $post_bean->id;
        $post_bean->user_id = (int) $post_bean->user_id;

        return ok($post_bean);
    }
);