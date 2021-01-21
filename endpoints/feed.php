<?php
require_once "util/Request.php";
require_once "util/http_build_url.php";

if (!isset($collector)) return;

$collector->get(
    "/feed",
    function() {
        $tokenizer = new Tokenizer(ACCESS_SECRET);
        ["valid" => $valid, "payload" => $payload] = $tokenizer->decodeToken(Request::accessToken());
        if (!$valid) return unauthorized("Invalid token");

        $user_bean = R::findOne("user", "id = ?", [ $payload->id ]);
        if ($user_bean === null) return error("User not found");

        [ "limit" => $limit, "offset" => $offset ] = Request::page();

        $total = R::count("session");
        $posts = R::findAll("session", "LIMIT ?, ?", [ $offset, $limit ]);

        $meta = [];
        $meta[] = pagination($limit, $offset, $total);

        return ok($posts, $meta);
    }
);