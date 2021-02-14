<?php
require_once "util/Request.php";
require_once "util/http_build_url.php";

if (!isset($collector)) return;

$collector->get(
    "/user/{username:[a-zA-Z0-9-_]{2,30}}/posts",
    function($username) {
        $user_bean = R::findOne("user", "username LIKE :username", [ ":username" => $username ]);
        if ($user_bean === null) return error("User not found");

        [ $limit, $offset ] = Request::page();

        $total = $user_bean->countOwn("post");
        $post_beans = R::findAll("post", "WHERE user_id = ? LIMIT ?, ?", [ $user_bean->id, $offset, $limit ]);

        $posts = [];
        foreach ($post_beans as $post) {
            $post->id = (int) $post->id;
            $post->user_id = (int) $post->user_id;
            $post->created = (int) $post->created;
            $post->content = json_decode($post->content);
            $posts[] = $post;
        }

        return ok($posts, pagination($limit, $offset, $total));
    }
);