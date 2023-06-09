<?php

require_once __DIR__ . "/../bootstrap.php";

use Api\Utils\Auth\User;

$uid = "0fd65450";

echo new User($uid);
