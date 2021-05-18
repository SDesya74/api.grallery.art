<?php

use PHPMailer\PHPMailer\PHPMailer;
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

    public function register(string $username, string $visible_name, string $email, string $password) {
        $password_hash = password_hash($password, PASSWORD_DEFAULT);

        $this->username = $username;
        $this->visible_name = $visible_name;
        $this->email = $email;
        $this->confirmed = false;
        $this->password_hash = $password_hash;
        $this->avatar = "https://www.gravatar.com/avatar/" . md5(strtolower(trim($email))) . "?d=mp";
        // TODO: Create random avatar generator or just select random avatar image for new user
        $this->created = time();
        $this->last_enter = time();
    }

    public function verifyPassword($password): bool {
        return password_verify($password, $this->password_hash);
    }

    public function sendConfirmationEmail(): array {
        if ($this->confirmed) return error("User is already confirmed");

        $confirmation = R::findOrCreate("confirmation", [ "user_id" => $this->id ]);
        $confirmation->created = time();
        $confirmation->user = $this->bean;
        R::store($confirmation);

        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->CharSet = "UTF-8";
            $mail->Host = "mail.hosting.reg.ru";
            $mail->SMTPAuth = true;
            $mail->Username = "noreply@grallery.art";
            $mail->Password = "mP6oQ0zP3wcY3y";
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;

            //Recipients
            $mail->setFrom("noreply@grallery.art", "Ratat.art");
            $mail->addAddress($this->email, $this->visible_name);

            //Content
            $mail->isHTML(true);
            $mail->Subject = "Confirm your E-mail";
            $context = [
                "visible_name" => $this->visible_name,
                "link" => "{$_SERVER["HTTP_ORIGIN"]}/verify/$confirmation->id"
            ];

            $content = file_get_contents("confirmation/email-template.html");
            $content = preg_replace_callback(
                "/{{\s*([a-zA-Z_][a-zA-Z\d+_]*)\s*}}\s*/",
                function ($matches) use ($context) {
                    return $context[$matches[1]];
                },
                $content
            );
            $mail->Body = $content;

            $mail->send();

            $index = strpos($this->email, "@") + 1;
            $domain = substr($this->email, $index);

            $mail = R::findOne("emailservice", " domain LIKE ? ", [ $domain ]);
            if($mail !== null) return ok([ "name" => $mail->name, "url" => $mail->url ]);
            return ok();
        } catch (Exception $e) {
            return error("Message could not be sent. Mailer Error: { $mail->ErrorInfo }");
        }
    }

    public function getLink(): string {
        return "/user/{$this->username}";
    }

    public function getFields(array $fields = []): object {
        return FieldFilter::filter($this, $fields, [ "password_hash" ]);
    }

    public function addPost(string $content) {
        $post = R::dispense("post");
        $post->content = json_encode($content);
        $post->created = time();

        $this->ownPostList[] = $post;
    }

    public function __jsonSerialize() {
        return [
            "id" => $this->id,
            "username" => $this->username,
            "visible_name" => $this->visible_name,
            "avatar" => $this->avatar,
            "created" => $this->created,
            "confirmed" => !!$this->confirmed,
            "last_enter" => $this->last_enter
        ];
    }
}
