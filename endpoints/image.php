<?php

$collector->post(
    "/image",
    function () {
        $json = Request::json();

        $errors = [];
        if (!isset($json->image)) $errors["image"] = "Missing Field";
        if (!empty($errors)) return error($errors);

        [ "image" => $image_from_cdn ] = $json;

        $userID = AccessToken::get()->getUserID();
        $user = Model_User::byID($userID);
        if ($user == null) return error("User not found");

        [ $encoded_server, $left, $right, $image_size, $size /*, $mime */ ] = explode("|", $image_from_cdn);
        $server = base64_decode($encoded_server);
        [ $width, $height ] = explode("x", $image_size);

        $image = R::dispense("image");
        $image->server = $server;
        $image->left = $left;
        $image->right = $right;

        $image->width = $width;
        $image->height = $height;
        $image->size = $size;

        $image->user = $user;

//        if (isset($json->album_id)) {
//            $album = R::load("album", $json->album_id);
//            if ($album == null) return error("Album not found");
//            $image->album = $album;
//        }

        R::store($image);

        return ok($image);
    },
    [ "before" => "auth" ]
);

$uuid_regex = UUID_REGEX;
$collector->get(
    "/image/{id:$uuid_regex}",
    function ($id) {
        $image = Model_Image::byID($id);
        return ok($image);
    }
);