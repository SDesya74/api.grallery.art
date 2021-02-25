<?php

use JetBrains\PhpStorm\ArrayShape;
use RedBeanPHP\OODBBean;

class Model_Session extends RedBean_SimpleModel {
    #[ArrayShape([ "access" => "\ArrayObject", "refresh" => "\ArrayObject" ])]
    public static function createForUser(
        OODBBean $user
    ): array {
        $access = AccessToken::create($user->getID(), new AccessRights());
        $refresh = RefreshToken::create($user->getID());

        // create session bean
        $session_bean = R::dispense("session");
        $session_bean->refresh_token = $refresh->token;
        $session_bean->expires = $refresh->expires;
        $session_bean->user_agent = Request::header("User-Agent");

        // save refresh token to database
        $user->ownSessionList[] = $session_bean;

        return [ "access" => $access, "refresh" => $refresh ];
    }
}
