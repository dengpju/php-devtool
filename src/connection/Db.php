<?php

namespace PHPDTool\connection;

use PDO;
use PDOException;

class Db
{
    protected $drive = 'mysql';
    protected $host = '192.168.8.99';
    protected $port = 3306;
    protected $database = 'tianli_lida_children';
    protected $user = 'root';
    protected $password = '1qaz2wsx';
    protected $dsn = "";
    protected $charset = "utf8mb4";

    public function __construct()
    {
        $this->dsn = "$this->drive:host=$this->host;port=$this->port;dbname=$this->database;charset=$this->charset";
    }

    public function connect()
    {
        try {
            $db = new PDO($this->dsn, $this->user, $this->password, [PDO::ATTR_PERSISTENT => true]);
            $statement = "SELECT TABLE_NAME as Name,TABLE_COMMENT as Comment FROM information_schema.TABLES WHERE table_schema='{$this->database}'";
            echo $statement . PHP_EOL;
            foreach ($db->query($statement) as $row) {
                print_r($row);
            }
        } catch (PDOException $e) {
            die ("Error!: " . $e->getMessage() . PHP_EOL);
        }

    }

}