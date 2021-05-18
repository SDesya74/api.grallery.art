<?php
require_once "variables/cdn.php";

$collector->get(
    "/upload/image",
    function () {
        $args = Request::args();
        if (!isset($args->hash)) return error("Where hash?");
        $hash = htmlspecialchars($args->hash);

        $addresses = get_all_cdn_addresses();
        shuffle($addresses); // for more randomness

        foreach ($addresses as $cdn) {
            try {
                $url = $cdn . "/available";
                $url .= "?" . http_build_query([ "hash" => $hash ]);

                $curl = curl_init($url);
                curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
                $response = curl_exec($curl);
                curl_close($curl);

                if ($response === false) continue;

                $json = json_decode($response, true);
                if ($json === false) continue;

                if (!$json["ok"]) continue;

                $payload = $json["payload"];
                if ($payload["available"] !== true) return error("Flag already exists");

                return ok([ "upload_url" => $payload["upload_url"] ]);
            } catch (Exception $ignore) {
            }
        }
        return not_found("Available Server not found");
    },
    [ "before" => "auth" ]
);