<?php

// Path: api/src/Scheduler/Task.php

declare(strict_types=1);

namespace App\Scheduler;

use App\Core\Logger\ErrorLogger;
use Cron\CronExpression;

abstract class Task
{
    public readonly string $name;

    public string $id = '';

    protected CronExpression $cronExpression;

    protected bool $truthSetting = true;

    public function __construct(string $name)
    {
        $this->name = \preg_replace('/\s+/', '_', \trim(\mb_strtolower($name))) ?? $name;
    }

    final public function getId(): string
    {
        return $this->id;
    }

    final public function setCron(string $cronString): static
    {
        try {
            $this->cronExpression = new CronExpression($cronString);
        } catch (\Exception $e) {
            $errorMessage = sprintf(
                'Task %s: Invalid cron expression "%s"',
                $this->name,
                $cronString
            );

            ErrorLogger::log(new \InvalidArgumentException($errorMessage, previous: $e));
        }

        return $this;
    }

    /**
     * Set a condition for the task to run.
     * 
     * @param callable(): bool $callback Function that returns a boolean value.
     * 
     * @return static 
     */
    final public function when(callable $callback): static
    {
        $this->truthSetting = $callback();

        return $this;
    }

    final public function isDue(): bool
    {
        if (!isset($this->cronExpression)) {
            return false;
        }

        return $this->cronExpression->isDue();
    }

    abstract public function execute(): void;

    public function logSuccess(string $message): void
    {
        $message = sprintf(
            "[%s] %s: success\n%s",
            new \DateTime()->format('Y-m-d H:i:s'),
            $this->name,
            $message
        );

        $this->log($message);
    }

    public function logError(\Throwable $error): void
    {
        $message = sprintf(
            "[%s] %s: error\n%s",
            new \DateTime()->format('Y-m-d H:i:s'),
            $this->name,
            $error->getMessage()
        );

        $this->log($message);
    }

    protected function getLogFilePath(): string
    {
        return LOG_DIR . '/tasks/' . $this->name . '.log';
    }

    protected function log(string $message): void
    {
        // Make sure the directory exists
        if (!is_dir(LOG_DIR . '/tasks')) {
            mkdir(LOG_DIR . '/tasks', 0777, true);
        }

        // Make sure the message ends with two newlines
        $message = \trim($message) . PHP_EOL . PHP_EOL;

        file_put_contents($this->getLogFilePath(), $message, FILE_APPEND);
    }
}
