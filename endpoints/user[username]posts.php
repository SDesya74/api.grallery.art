<?php
require_once "util/Request.php";
require_once "util/http_build_url.php";

if (!isset($collector)) return;

$collector->get(
    "/user/{username:[a-zA-Z0-9-_]{2,30}}/posts",
    function($username) {
        $user_bean = R::findOne("user", "username LIKE :username", [ ":username" => $username ]);
        if ($user_bean === null) return error("User not found");

        $page = Request::page();

        $total = $user_bean->countOwn("post");
        $post_beans = R::findAll(
            "post",
            "WHERE user_id = :user_id LIMIT :offset, :limit",
            [
                ":user_id" => $user_bean->id,
                ":offset" => $page->offset,
                ":limit" => $page->limit
            ]
        );

        $posts = [];
        foreach ($post_beans as $post) {
            $post->id = (int) $post->id;
            $post->user_id = (int) $post->user_id;
            $post->created = (int) $post->created;
            $post->content = json_decode($post->content);
            $posts[] = $post;
        }

        $links = [];
        if ($page->offset + $page->limit < $total) $links["next"] = urldecode(
            http_build_url(
                $_SERVER["REQUEST_URI"],
                [ "query" => http_build_query(
                    [ "page" => [ "offset" => $page->offset + $page->limit ] ]
                ) ],
                HTTP_URL_STRIP_AUTH | HTTP_URL_JOIN_PATH | HTTP_URL_JOIN_QUERY | HTTP_URL_STRIP_FRAGMENT
            )
        );

        if ($page->offset >= $page->limit) $links["prev"] = urldecode(
            http_build_url(
                $_SERVER["REQUEST_URI"],
                [ "query" => http_build_query(
                    [ "page" => [ "offset" => $page->offset - $page->limit ] ]
                ) ],
                HTTP_URL_STRIP_AUTH | HTTP_URL_JOIN_PATH | HTTP_URL_JOIN_QUERY | HTTP_URL_STRIP_FRAGMENT
            )
        );

        return ok($posts, [ "page" => $page, "links" => $links ]);
    }
);