<?php

require_once __DIR__ . "/../vendor/autoload.php";

use App\Core\Auth\User;

$user = "bob";

$redis_host = "localhost";
$redis_port = 6380;

$redis = new Redis();
$redis->pconnect($redis_host, $redis_port);

(new User($user, $redis))->clearSessions();
