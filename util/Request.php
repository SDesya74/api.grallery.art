<?php

class Request {
    private static $parsed_args;

    static function getJsonFields(...$fields): ArrayObject {
        $json = self::json();

        if ($json == null) {
            return new ArrayObject(
                [ "valid" => false, "errors" => [ "Invalid JSON" ] ],
                ArrayObject::ARRAY_AS_PROPS
            );
        }

        $errors = [];
        $payload = new ArrayObject([], ArrayObject::ARRAY_AS_PROPS);
        foreach ($fields as $field) {
            if (isset($json[$field])) {
                $payload[$field] = $json[$field];
                continue;
            }
            $errors[$field] = "Missing field";
        }

        if (count($errors) > 0) $result = [ "valid" => false, "errors" => $errors ];
        else $result = [ "valid" => true, "payload" => $payload ];
        return new ArrayObject($result, ArrayObject::ARRAY_AS_PROPS);
    }

    static function json(): ?ArrayObject {
        try {
            return new ArrayObject(
                json_decode(file_get_contents("php://input")),
                ArrayObject::ARRAY_AS_PROPS
            );
        } catch (Exception $ex) {
            return null;
        }
    }

    static function fields($name = null) {
        $args = self::args();
        if (empty($args->fields)) return null;

        $fields = $args->fields;
        if ($name == null) return $fields;

        if (empty($fields[$name])) return null;
        return explode(",", $fields[$name]);
    }

    static function args(): ArrayObject {
        if (self::$parsed_args !== null) return self::$parsed_args;

        parse_str(parse_url($_SERVER["REQUEST_URI"], PHP_URL_QUERY), self::$parsed_args);
        self::$parsed_args = new ArrayObject(self::$parsed_args, ArrayObject::ARRAY_AS_PROPS);

        return self::$parsed_args;
    }

    static function page(): array {
        $args = self::args();
        return isset($args->page) ? [ $args->page->limit, $args->page->offset ] : [ 25, 0 ];
    }

    static function accessToken(): ?string {
        if (isset(self::args()->access_token)) return self::args()->access_token;

        $token = self::header("Authorization");
        if ($token !== null) return explode(" ", $token)[1];

        return null;
    }

    static function header($name) {
        $name = str_replace("-", "_", strtoupper($name));
        return empty($_SERVER["HTTP_$name"]) ? null : $_SERVER["HTTP_$name"];
    }
}

