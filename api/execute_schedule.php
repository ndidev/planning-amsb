#!/usr/bin/env php
<?php

// Path: api/execute_schedule.php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

use App\Scheduler\Scheduler;

const TODAY = new \DateTime();

$scheduler = new Scheduler();

/**
 * Tasks
 */



$scheduler->run();

echo 'Scheduler executed successfully' . PHP_EOL;
