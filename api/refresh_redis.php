<?php

/**
 * Script rafraichissant les données de Redis
 * après un relancement ou une mise à jour de la base de données.
 */

require_once __DIR__ . "/bootstrap.php";

use App\Core\Database\MySQL;
use App\Core\Database\Redis;
use App\Repository\UserRepository;
use App\Core\Auth\User;

$mysql = new MySQL();
$redis = new Redis();

// Users
(new UserRepository())->fetchAllUsers();

// Pays
$statement = "SELECT * FROM utils_pays ORDER BY nom";
$pays = $mysql->query($statement)->fetchAll();
$redis->set("pays", json_encode($pays));

// Ports
$statement = "SELECT * FROM utils_ports ORDER BY SUBSTRING(locode, 1, 2), nom";
$ports = $mysql->query($statement)->fetchAll();
$redis->set("ports", json_encode($ports));
