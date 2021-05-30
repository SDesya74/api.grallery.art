<?php

use JetBrains\PhpStorm\ArrayShape;

class Model_Session extends RedBean_SimpleModel {
    #[ArrayShape([ "access" => "\ArrayObject", "refresh" => "\ArrayObject", "server_time" => "integer" ])]
    public static function createForUser(Model_User $user): array {
        $user = $user->unbox();
        $access = AccessToken::create($user->getID(), new AccessRights());
        $refresh = RefreshToken::create($user->getID());

        // create session bean
        $session_bean = R::dispense("session");
        $session_bean->refresh_token = $refresh->token;
        $session_bean->expires = $refresh->expires;
        $session_bean->user_agent = Request::header("User-Agent");

        // save refresh token to database
        $user->ownSessionList[] = $session_bean;

        // remove old sessions
        $count = count($user->ownSessionList);
        $max = 10;
        if ($count >= $max) {
            $sessions = array_values($user->ownSessionList);
            $sessions_to_hunt = array_slice($sessions, 0, $count - $max);
            $ids = array_column($sessions_to_hunt, "id");
            R::hunt("session", "id IN (" . R::genSlots($ids) . ")", $ids);
        }

        return [ "access" => $access, "refresh" => $refresh, "server_time" => time() ];
    }
}
