<?php
require_once "util/Request.php";

$collector->post(
    "/confirm",
    function () {
        $user = Model_User::byID(AccessToken::get()->getUserID());
        if ($user == null) return not_found("User not found");
        if($user->confirmed) return error("User is already confirmed");

        return $user->sendConfirmationEmail();
    },
    [ "before" => "auth" ]
);