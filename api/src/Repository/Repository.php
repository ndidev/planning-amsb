<?php

// Path: api/src/Repository/Repository.php

declare(strict_types=1);

namespace App\Repository;

use App\Core\Database\MySQL;
use App\Core\Database\Redis;

abstract class Repository
{
    protected MySQL $mysql {
        get => self::$staticMysql ??= new MySQL();
    }

    protected Redis $redis {
        get => self::$staticRedis ??= new Redis();
    }

    static private ?MySQL $staticMysql = null;
    static private ?Redis $staticRedis = null;
}
