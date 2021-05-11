<?php

$collector->filter(
    "auth",
    function() {
        try {
            AccessToken::parseRequest();
        } catch (Exception $ex) {
            return unauthorized($ex->getMessage());
        }
    }
);