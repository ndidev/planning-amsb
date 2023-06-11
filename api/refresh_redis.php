<?php

require_once __DIR__ . "/bootstrap.php";

use Api\Utils\DatabaseConnector as DB;
use Api\Models\Admin\UserAccountModel;
use Api\Utils\Auth\User;

$db = (new DB)->getConnection();
$redis_host = $_ENV["REDIS_HOST"];
$redis_port = $_ENV["REDIS_PORT"];

$redis = new Redis();
$redis->pconnect($redis_host, $redis_port);

// Users
(new UserAccountModel(new User(null, $redis)))->readAll();

// Pays
$statement = "SELECT * FROM utils_pays ORDER BY nom";
$pays = $db->query($statement)->fetchAll();
$redis->set("pays", json_encode($pays));

// Ports
$statement = "SELECT * FROM utils_ports ORDER BY SUBSTRING(locode, 1, 2), nom";
$ports = $db->query($statement)->fetchAll();
$redis->set("ports", json_encode($ports));
