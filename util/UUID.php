<?php

use RedBeanPHP\Adapter;
use RedBeanPHP\QueryWriter;
use RedBeanPHP\QueryWriter\MySQL;

$UUID_LENGTH = 11;

class UUIDWriterMySQL extends MySQL implements QueryWriter {
    const C_DATATYPE_SPECIAL_UUID = 97;
    protected $defaultValue = "@uuid";

    public function __construct(Adapter $adapter) {
        global $UUID_LENGTH;
        parent::__construct($adapter);
        $this->addDataType(
            self::C_DATATYPE_SPECIAL_UUID,
            "char($UUID_LENGTH)");
    }

    public function createTable($table) {
        global $UUID_LENGTH;
        $table = $this->esc($table);
        $sql = "CREATE TABLE {$table} (
                id char($UUID_LENGTH) NOT NULL,
                PRIMARY KEY (id))
                ENGINE = InnoDB DEFAULT
                CHARSET = utf8mb4
                COLLATE = utf8mb4_unicode_ci";
        $this->adapter->exec($sql);
    }

    public function updateRecord($table, $updateValues, $id = null) {
        global $UUID_LENGTH;

        $flagNeedsReturnID = (!$id);

        do $uid = $this->generateUid($UUID_LENGTH);
        while (R::count($table, "id = ?", [ $uid ]) > 0);

        if ($flagNeedsReturnID) R::exec("SET @uuid = ?;", [ $uid ]);
        $id = parent::updateRecord($table, $updateValues, $id);
        if ($flagNeedsReturnID) $id = R::getCell("SELECT @uuid");
        return $id;
    }

    private function generateUid($length) {
        $chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ_-'; // [A-Za-z-_]{11}
        $chars_length = strlen($chars);
        $uid = '';
        while ($length-- > 0) $uid .= $chars[random_int(0, $chars_length)];
        return $uid;
    }

    public function getTypeForID() {
        return self::C_DATATYPE_SPECIAL_UUID;
    }
}