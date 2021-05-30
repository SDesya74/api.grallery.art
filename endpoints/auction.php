<?php

$collector->post(
    "/auction",
    function () {
        $json = Request::json();

        $errors = [];
        if (!isset($json->title)) $errors["title"] = "Missing Field";
        if (!isset($json->preview_id)) $errors["preview_id"] = "Missing Field";
        if (!isset($json->lots)) $errors["lots"] = "Missing Field";
        if (!empty($errors)) return error($errors);

        $userID = AccessToken::get()->getUserID();
        $user = Model_User::byID($userID);
        if ($user == null) return not_found("User not found");

        $preview = R::findOne("image", "id = ?", [ $json->preview_id ]);
        if ($preview == null) return not_found("Preview image not found");

        $title = $json->title;
        if(strlen($title) > 50) return error("Too long auction title");

        $description = $json->description ?? null;
        $tags = array_values((array) $json->tags) ?? [];
        $start = $json->date->from ?? time();
        $end = $json->date->to ?? time() + 60 * 60 * 24 * 7;

        $auction = R::dispense("auction");
        $auction->title = $title;
        $auction->description = $description;
        $auction->preview = $preview;

        $auction->created = time();
        $auction->start = $start;
        $auction->end = $end;

        $auction->user = $user;

        R::tag($auction, $tags);

        $lots = array_values((array) $json->lots);
        if (sizeof($lots) < 1) return error("Cannot create auction without lots");

        foreach ($lots as $item) {
            $lot = R::dispense("lot");
            $lot->title = $item->title;
            if(strlen($lot->title) > 50) return error("Too long lot title");

            $lot->description = $item->description ?? null;

            $lot->opening_price = floatval($item->opening_price);
            $lot->increment = floatval($item->increment);
            $lot->buyout_price = floatval($item->buyout_price);

            $preview = R::findOne("image", "id = ?", [ $item->preview_id ]);
            if ($preview == null) return error("Lot preview image not found");
            $lot->preview = $preview;

            $auction->ownLotList[] = $lot;
        }

        R::store($auction);

        return ok($auction);
    },
    [ "before" => "auth" ]
);


$collector->get(
    "/auction/{id:$uuid_regex}",
    function ($id) {
        $auction = R::findOne("auction", 'id = ?', [ $id ]);
        if ($auction == null) return not_found("Auction not found");

        return ok($auction);
    }
);
