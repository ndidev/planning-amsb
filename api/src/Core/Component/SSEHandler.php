<?php

// Path: api/src/Core/Component/SSEHandler.php

declare(strict_types=1);

namespace App\Core\Component;

use App\Core\Array\Environment;
use App\Core\Array\Server;
use App\Core\Interfaces\Arrayable;
use App\Core\Logger\ErrorLogger;

/**
 * Server-Sent Events.
 * 
 */
class SSEHandler
{
    /**
     * Events to be sent to the SSE server.
     * @var list<
     *        array{
     *          name: string,
     *          type: string,
     *          id: int|string,
     *          data: mixed,
     *          origin: string|null,
     *        }
     *      >
     */
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
            "origin" => Server::getString('HTTP_X_SSE_CONNECTION', null),
        ];
    }

    /**
     * Notify the SSE server of modifications to the database.
     */
    public function notify(): void
    {
        $host = Environment::getString('SSE_HOST');
        $port = Environment::getInt('SSE_UPDATES_PORT');

        if (!$host || !$port) {
            ErrorLogger::log(new \Exception("L'hôte et le port du serveur SSE ne sont pas définis"));
            return;
        }

        $url = "http://{$host}:{$port}";

        $options = [
            "http" => [
                "header" => "Content-type: application/json\r\n",
                "method" => "POST",
                "content" => \json_encode($this->events),
                "timeout" => 0.5,
            ]
        ];

        $context = stream_context_create($options);
        $result = \file_get_contents($url, false, $context);

        if ($result === false) {
            ErrorLogger::log(new \Exception("Erreur de notification SSE"));
        }
    }
}
