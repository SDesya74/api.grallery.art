<?php

class Model_Auction extends RedBean_SimpleModel {
    public function __jsonSerialize() {
        return [
            "id" => $this->id,
            "title" => $this->title,
            "description" => $this->description,
            
            "author" => $this->user,
            
            "created" => $this->created,
            "start" => (int) $this->start,
            "end" => (int) $this->end,

            "preview" => R::load("image", $this->preview_id),
            "lots" => array_values($this->ownLotList),

            "tags" => R::tag($this->bean)
        ];
    }
}