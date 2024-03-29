<?php
function build_url($parsed_url): string {
    $scheme = isset($parsed_url['scheme']) ? $parsed_url['scheme'] . '://' : '';
    $host = $parsed_url['host'] ?? '';
    $port = isset($parsed_url['port']) ? ':' . $parsed_url['port'] : '';
    $user = $parsed_url['user'] ?? '';
    $pass = isset($parsed_url['pass']) ? ':' . $parsed_url['pass'] : '';
    $pass = ($user || $pass) ? "$pass@" : '';
    $path = $parsed_url['path'] ?? '';
    $query = isset($parsed_url['query']) ? '?' . $parsed_url['query'] : '';
    $fragment = isset($parsed_url['fragment']) ? '#' . $parsed_url['fragment'] : '';
    return "$scheme$user$pass$host$port$path$query$fragment";
}
