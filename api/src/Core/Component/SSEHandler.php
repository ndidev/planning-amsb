<?php

namespace App\Core\Component;

use App\Core\Interfaces\Arrayable;
use App\Core\Logger\ErrorLogger;

/**
 * Server-Sent Events.
 * 
 */
class SSEHandler
{
    private array $events = [];

    /**
     * Add an event to the SSE handler.
     * 
     * @param string     $name Name of the event.
     * @param string     $type Type of modification.
     * @param int|string $id   ID of the modified resource.
     */
    public function addEvent(string $name, string $type, int|string $id, mixed $data = null): void
    {
        $this->events[] = [
            "name" => $name,
            "type" => $type,
            "id" => $id,
            "data" => $data instanceof Arrayable ? $data->toArray() : $data,
            "origin" => $_SERVER["HTTP_X_SSE_CONNECTION"] ?? NULL,
        ];
    }

    /**
     * Notify the SSE server of modifications to the database.
     */
    public function notify(): void
    {
        $url = "http://{$_ENV["SSE_HOST"]}:{$_ENV["SSE_UPDATES_PORT"]}";

        foreach ($this->events as $key => $event) {
            $options = [
                "http" => [
                    "header" => "Content-type: application/json\r\n",
                    "method" => "POST",
                    "content" => json_encode($event),
                    "timeout" => 0.5,
                ]
            ];

            $context = stream_context_create($options);
            $result = file_get_contents($url, false, $context);

            if ($result === false) {
                ErrorLogger::log(new \Exception("Erreur de notification SSE"));
            }
        }
    }
}
