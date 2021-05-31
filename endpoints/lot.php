<?php

$collector->get(
    "/lot/{id:$uuid_regex}",
    function ($id) {
        $lot = R::findOne("lot", 'id = ?', [ $id ]);
        if ($lot == null) return not_found("Lot not found");

        return ok($lot);
    }
);

$collector->post(
    "/lot/{id:$uuid_regex}/bid",
    function ($id) {
        $json = Request::getJsonFields("value");
        if (!$json->valid) return error($json->errors);

        [ "value" => $value ] = $json->payload;

        $lot = R::findOne("lot", 'id = ?', [ $id ]);
        if ($lot == null) return not_found("Lot not found");

        $user = Model_User::byID(AccessToken::get()->getUserID());
        if ($user == null) return not_found("User not found");

        $bid = R::dispense("bid");
        $bid->value = $value;
        $bid->user = $user;
        $bid->created = time();

        $lot->ownBidList[] = $bid;

        R::store($lot);

        return ok($bid);
    },
    [ "before" => "auth" ]
);