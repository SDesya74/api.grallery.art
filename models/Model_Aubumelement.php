<?php

class Model_Albumelement extends RedBean_SimpleModel {
    public function __jsonSerialize(): array {
        return [
            "id" => $this->id,
            "title" => $this->title,
            "description" => $this->description,
            "created" => $this->created,

            "tags" => R::tag($this->bean),
            "image" => $this->image,
            "album_id" => $this->album->id
        ];
    }
}