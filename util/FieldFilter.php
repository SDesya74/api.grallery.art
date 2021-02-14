<?php

class FieldFilter {
    static function filter(object $object, array $allow = [], array $block = []): object {
        foreach ($object as $key => $value)
            if (in_array($key, $block) || (!empty($allow) && !in_array($key, $allow)))
                unset($object[$key]);
        return $object;
    }
}