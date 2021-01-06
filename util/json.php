<?php
function get_posted_json($assoc = true) {
    return json_decode(file_get_contents("php://input"), $assoc);
}