<?php

class Model_User extends RedBean_SimpleModel {
    public function createSession(): array {
        // TODO: Remove User-Agent in sessions (replace it with max session amount)
        $access_tokenizer = new Tokenizer(ACCESS_SECRET);
        $refresh_tokenizer = new Tokenizer(REFRESH_SECRET);

        // create token pair
        $access = $access_tokenizer->generateToken([ "id" => $this->bean->getID() ], ACCESS_TOKEN_LIFETIME);
        $refresh = $refresh_tokenizer->generateToken([], REFRESH_TOKEN_LIFETIME);

        // create session bean
        $session_bean = R::dispense("session");
        $session_bean->refresh_token = $refresh->token;
        $session_bean->expires = $refresh->expires;
        $session_bean->user_agent = Request::header("User-Agent");

        // save refresh token to database
        $this->bean->ownSessionList[] = $session_bean;

        return [ "access" => $access, "refresh" => $refresh ];
    }

    public function register($username, $email, $password) {
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        $nickname = $username;

        $bean = $this->bean;

        $bean->username = $username;
        $bean->nickname = $nickname;
        $bean->email = $email;
        $bean->password_hash = $password_hash;
        $bean->avatar = "https://www.gravatar.com/avatar/" . md5(strtolower(trim($email)));
        $bean->last_enter = time();
        $bean->created = time();
        $bean->active = false;

        $bean->ownPostList = [];
    }

    public function verifyPassword($password): bool {
        return password_verify($password, $this->bean->password_hash);
    }

    public function sendConfirmationEmail() {

    }

    public function getLink(): string {
        return "/user/{$this->bean->username}";
    }

    public function getFields(array $fields = []): object {
        return FieldFilter::filter($this->bean, $fields, [ "password_hash" ]);
    }
}
