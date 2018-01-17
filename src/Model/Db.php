<?php
namespace Model;

use Medoo\Medoo;

class Db
{
    /**
     * 保留数据库连接资源
     *
     * @var array
     */
    public static $conn = [];

    private function __construct(){}

    private function __clone(){}

    public static function getInstance($dbName)
    {
        if (empty(self::$conn[$dbName]))
        {
            self::$conn[$dbName] = new Medoo([
                'database_type' => 'mysql',
                'database_name' => $dbName,
                'server'        => '172.16.100.93',
                'username'      => 'mbang_test',
                'password'      => 'mbang_test@321'
            ]);
        }

        return self::$conn[$dbName];
    }

}