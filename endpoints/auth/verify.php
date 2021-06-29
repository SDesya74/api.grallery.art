<?php
require_once "util/Request.php";

$collector->post(
    "/verify/{id:$uuid_regex}",
    function ($id) {
        $confirmation = R::findOne("confirmation", " id LIKE ?", [ $id ]);
        if ($confirmation == null) return not_found("Confirmation not found. Maybe your email is already confirmed?");

        $user = $confirmation->user;
        if ($user == null) return not_found("Confirmation user not found");

        if ($user->confirmed) return error("User is already confirmed");

        if (abs(time() - $confirmation->created) > 60 * 60) return error("Confirmation link expired");

        $user->confirmed = true;
        R::store($user);
        R::trash($confirmation);

        return ok($user);
    }
);