<?php

use RedBeanPHP\OODBBean;

class Model_User extends RedBean_SimpleModel {
    public static function byID(string $id): ?OODBBean {
        return self::findOneBy("id LIKE ?", [ $id ]);
    }

    public static function findOneBy(string $sql, array $bindings): ?OODBBean {
        return R::findOne("user", $sql, $bindings);
    }

    public static function byUsernameOrEmail(string $login): ?OODBBean {
        return self::findOneBy("username LIKE :login or email LIKE :login", [ ":login" => $login ]);
    }

    public function register(string $username, string $email, string $password) {
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        $nickname = $username;

        $bean = $this->bean;

        $bean->username = $username;
        $bean->nickname = $nickname;
        $bean->email = $email;
        $bean->password_hash = $password_hash;
        $email_hash = md5(strtolower(trim($email)));
        $bean->avatar = "https://www.gravatar.com/avatar/$email_hash?d=mp";
        $bean->created = time();
        $bean->last_enter = time();
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

    public function addPost(string $content) {
        $post_bean = R::dispense("post");
        $post_bean->content = json_encode($content);
        $post_bean->created = time();

        $this->bean->ownPostList[] = $post_bean;
    }
}
