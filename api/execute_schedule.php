#!/usr/bin/env php
<?php

// Path: api/execute_schedule.php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

use App\Core\Component\DateUtils;
use App\Scheduler\Scheduler;
use App\Scheduler\Tasks\CreateFeedBulkAppointment;

const TODAY = new \DateTime();

$scheduler = new Scheduler();

/**
 * Tasks
 */

// Vracs agro
$scheduler
    ->registerTask(new CreateFeedBulkAppointment('creer_rdv_vracs_agro'))
    ->setCron('0 0 * * *') // Every day at midnight
    ->when(fn() => DateUtils::isWorkingDay(TODAY));

$scheduler->run();

echo 'Scheduler executed successfully' . PHP_EOL;
