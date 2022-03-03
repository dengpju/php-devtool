<?php


namespace PHPDTool\connection;


class DbConfig
{
    public $drive = 'mysql';
    public $host = '192.168.8.99';
    public $port = 3306;
    public $database = 'tianli_lida_children';
    public $user = 'root';
    public $password = '1qaz2wsx';
    public $charset = "utf8mb4";

    public function dsn(): string
    {
        return "$this->drive:host=$this->host;port=$this->port;dbname=$this->database;charset=$this->charset";
    }
}