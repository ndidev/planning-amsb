<?php

require_once __DIR__ . "/bootstrap.php";

use Api\Models\Admin\UserAccountModel;
use Api\Utils\Auth\User;

$redis_host = $_ENV["REDIS_HOST"];
$redis_port = $_ENV["REDIS_PORT"];

$redis = new Redis();
$redis->pconnect($redis_host, $redis_port);

(new UserAccountModel(new User(null, $redis)))->readAll();
