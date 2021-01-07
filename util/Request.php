<?php

class Request {
    private static $parsed_args;

    static function json() {
        return new ArrayObject(json_decode(file_get_contents("php://input")), ArrayObject::ARRAY_AS_PROPS);
    }

    static function fields($name = null) {
        $args = self::args();
        if (empty($args->fields)) return null;

        $fields = $args->fields;
        if ($name == null) return $fields;

        if (empty($fields[$name])) return null;
        return explode(",", $fields[$name]);
    }

    static function args() {
        if (self::$parsed_args !== null) return self::$parsed_args;

        parse_str(parse_url($_SERVER["REQUEST_URI"], PHP_URL_QUERY), self::$parsed_args);
        self::$parsed_args = new ArrayObject(self::$parsed_args, ArrayObject::ARRAY_AS_PROPS);

        return self::$parsed_args;
    }

    static function page() {
        $args = self::args();
        if (empty($args->page)) return null;
        return $args->page;
    }

    static function accessToken() {
        if (isset(self::args()->access_token)) return self::args()->access_token;

        $token = self::header("Authorization");
        if ($token !== null) return explode(" ", $token)[1];

        return null;
    }

    static function header($name) {
        $headers = getallheaders();
        return isset($headers[$name]) ? $headers[$name] : null;
    }
}

