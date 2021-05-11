<?php
require_once "util/responses.php";
require_once "util/Request.php";
require_once "variables/token.php";

$collector->post(
    "/refresh",
    function () {
        $json = Request::getJsonFields("refresh_token");
        if (!$json->valid) return error($json->errors);

        $refresh_token = $json->payload->refresh_token;
        try {
            $payload = RefreshToken::decode($refresh_token);
        } catch (Exception $ex) {
            return unauthorized($ex->getMessage());
        }

        $user = Model_User::byID($payload->getUserID());
        if ($user === null) return error("User not found");

        $sessions = $user->withCondition("refresh_token LIKE ?", [ $refresh_token ])->ownSessionList;
        if (count($sessions) < 1) return error("Session not found");
        R::trashAll($sessions);

        $user = $user->fresh();

        $new_session = Model_Session::createForUser($user);
        R::store($user);

        return ok($new_session, hateoas("user", "/user/$user->username"));
    }
);