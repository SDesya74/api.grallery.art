<?php
require_once "util/Request.php";

$collector->get(
    "/me",
    function () {
        $token = AccessToken::get();
        $user_bean = Model_User::byID($token->getUserID());
        if ($user_bean == null) return error("User not found");

        $username = $user_bean->username;

        return ok($user_bean, hateoas("posts", "user/$username/posts"));
    },
    [ "before" => "auth" ]
);

$collector->get(
    "/auctions",
    function () {
        $userID = AccessToken::get()->getUserID();
        $user = Model_User::byID($userID);
        if ($user == null) return not_found("User not found");

        return ok($user->ownAuctionList);
    },
    [ "before" => "auth" ]
);