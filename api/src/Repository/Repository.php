<?php

namespace App\Repository;

use App\Core\Database\MySQL;
use App\Core\Database\Redis;

abstract class Repository
{

    protected MySQL $mysql;
    protected Redis $redis;

    static private ?MySQL $staticMysql = null;
    static private ?Redis $staticRedis = null;

    public function __construct()
    {
        $this->mysql = $this->getMySQLConnection();
        $this->redis = $this->getRedisConnection();
    }

    private function getMySQLConnection(): MySQL
    {
        if (!static::$staticMysql) {
            $this->makeMySQLConnection();
        }

        return static::$staticMysql;
    }

    private function makeMySQLConnection(): void
    {
        if (static::$staticMysql instanceof MySQL) {
            return;
        }

        static::$staticMysql = new MySQL();
    }

    private function getRedisConnection(): Redis
    {
        if (!static::$staticRedis) {
            $this->makeRedisConnection();
        }

        return static::$staticRedis;
    }

    private function makeRedisConnection(): void
    {
        if (static::$staticRedis instanceof Redis) {
            return;
        }

        static::$staticRedis = new Redis();
    }
}
