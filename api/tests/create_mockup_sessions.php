<?php

require_once __DIR__ . "/../vendor/autoload.php";

$users = [
  "alice",
  "bob",
  "charlie"
];

$redis_host = "localhost";
$redis_port = 6380;

$redis = new Redis();
$redis->pconnect($redis_host, $redis_port);

$redis->pipeline();

foreach ($users as $user) {
  for ($i = 0; $i < 5; $i++) {
    $session = md5(uniqid());
    $redis->set("admin:sessions:$session", $user);
  }
}

$redis->exec();

$redis->close();
