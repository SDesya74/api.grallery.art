<?php

use RedBeanPHP\Adapter;
use RedBeanPHP\QueryWriter;
use RedBeanPHP\QueryWriter\MySQL;

class UUIDWriterMySQL extends MySQL implements QueryWriter {
    const C_DATATYPE_SPECIAL_UUID = 97;
    protected $defaultValue = "@uuid";

    public function __construct(Adapter $adapter) {
        parent::__construct($adapter);
        $this->addDataType(
            self::C_DATATYPE_SPECIAL_UUID,
            sprintf("char(%s)", UUID_LENGTH)
        );
    }

    public function createTable($table) {
        $table = $this->esc($table);
        $sql = sprintf(
            "CREATE TABLE %s (id char(%s) NOT NULL, PRIMARY KEY (id)) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci",
            $table,
            UUID_LENGTH
        );
        $this->adapter->exec($sql);
    }

    public function updateRecord($table, $updateValues, $id = null): string {
        $flagNeedsReturnID = (!$id);

        do $uid = $this->generateUid(UUID_LENGTH);
        while (R::count($table, "id = ?", [ $uid ]) > 0);

        if ($flagNeedsReturnID) R::exec("SET @uuid = ?;", [ $uid ]);
        $id = parent::updateRecord($table, $updateValues, $id);
        if ($flagNeedsReturnID) $id = R::getCell("SELECT @uuid");
        return $id;
    }

    private function generateUid(int $length): string {
        $uid = '';
        $chars_length = strlen(UUID_ALPHABET) - 1;
        while ($length-- > 0) $uid .= UUID_ALPHABET[random_int(0, $chars_length)];
        return $uid;
    }

    public function getTypeForID(): string {
        return self::C_DATATYPE_SPECIAL_UUID;
    }
}