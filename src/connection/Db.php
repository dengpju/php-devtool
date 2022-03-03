<?php

namespace PHPDTool\connection;

use PDO;
use PDOException;

class Db
{
    /**
     * @var DbConfig
     */
    protected $dbConfig;

    public function __construct(DbConfig $dbConfig)
    {
        $this->dbConfig = $dbConfig;
    }

    public function connect()
    {
        try {
            $db = new PDO($this->dbConfig->dsn(), $this->dbConfig->user, $this->dbConfig->password, [PDO::ATTR_PERSISTENT => true]);
            $statement = "SELECT TABLE_NAME as Name,TABLE_COMMENT as Comment FROM information_schema.TABLES WHERE table_schema='{$this->dbConfig->database}'";
            echo $statement . PHP_EOL;
            foreach ($db->query($statement) as $row) {
                print_r($row);
            }
        } catch (PDOException $e) {
            die ("Error!: " . $e->getMessage() . PHP_EOL);
        }

    }

}