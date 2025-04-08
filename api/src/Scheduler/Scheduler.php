<?php

// Path: api/src/Scheduler/Scheduler.php

declare(strict_types=1);

namespace App\Scheduler;

final class Scheduler
{
    /** @var Task[] */
    private array $tasks = [];

    public function __construct()
    {
        // Initialize the scheduler
    }

    public function registerTask(Task $task): Task
    {
        if (!\in_array($task, $this->tasks, true)) {
            $task->id = uniqid();
            $this->tasks[] = $task;
        }

        return $task;
    }

    public function run(): void
    {
        foreach ($this->tasks as $task) {
            try {
                echo sprintf('Running task: %s' . PHP_EOL, $task->name);

                if (!$task->isDue()) {
                    echo sprintf('Task %s is not due yet' . PHP_EOL, $task->name);
                    continue;
                }

                $task->execute();

                $task->logSuccess('Task executed successfully');
            } catch (\Throwable $th) {
                $task->logError($th);
            }
        }
    }
}
