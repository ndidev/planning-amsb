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
        if (!self::$staticMysql) {
            $this->makeMySQLConnection();
        }

        /** @var MySQL self::$staticMysql */

        return self::$staticMysql;
    }

    private function makeMySQLConnection(): void
    {
        if (self::$staticMysql instanceof MySQL) {
            return;
        }

        self::$staticMysql = new MySQL();
    }

    private function getRedisConnection(): Redis
    {
        if (!self::$staticRedis) {
            $this->makeRedisConnection();
        }

        /** @var Redis self::$staticRedis */

        return self::$staticRedis;
    }

    private function makeRedisConnection(): void
    {
        if (self::$staticRedis instanceof Redis) {
            return;
        }

        self::$staticRedis = new Redis();
    }
}
