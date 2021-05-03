<?php

use JetBrains\PhpStorm\ArrayShape;

class Request {
    private static ?ArrayObject $parsed_args = null;

    #[ArrayShape([ "valid" => "bool", "payload" => "\ArrayObject", "errors" => "array" ])]
    static function getJsonFields(
        ...$fields
    ): ArrayObject {
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

    static function json(): ArrayObject {
        $input = file_get_contents("php://input");

        return new ArrayObject(
            $input ? json_decode($input) : [],
            ArrayObject::ARRAY_AS_PROPS
        );

    }

    static function fields($name = null): array {
        $args = self::args();
        if (empty($args->fields)) return [];

        $fields = $args->fields;
        if ($name == null) return $fields;

        if (empty($fields[$name])) return [];
        return explode(",", $fields[$name]) ?? [];
    }

    static function args(): ArrayObject {
        if (self::$parsed_args !== null) return self::$parsed_args;

        $args = null;
        parse_str(parse_url($_SERVER["REQUEST_URI"], PHP_URL_QUERY), $args);
        self::$parsed_args = new ArrayObject($args, ArrayObject::ARRAY_AS_PROPS);

        return self::$parsed_args;
    }

    static function page(): array {
        $args = self::args();
        return isset($args->page) ? [ $args->page->offset, $args->page->limit ] : [ 0, 25 ];
    }

    static function header($name) {
        $name = str_replace("-", "_", strtoupper($name));
        return empty($_SERVER["HTTP_$name"]) ? null : $_SERVER["HTTP_$name"];
    }
}

