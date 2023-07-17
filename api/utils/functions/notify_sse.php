<?php

require_once __DIR__ . "/../../bootstrap.php";

/**
 * Notifier le serveur SSE d'une modification à la base de données.
 * 
 * @param string     $name Nom de l'événement SSE
 * @param string     $type Type de modification
 * @param int|string $id   id de la ressource modifiée
 * 
 * @return string|false 
 */
function notify_sse(string $name, string $type, int|string $id, mixed $data = null): string|false
{
  $host = $_ENV["SSE_HOST"];
  $port = $_ENV["SSE_UPDATES_PORT"];
  $url = "http://$host:$port";

  $event = [
    "name" => $name,
    "type" => $type,
    "id" => $id,
    "data" => $data,
    "origin" => $_SERVER["HTTP_X_SSE_CONNECTION"] ?? NULL,
  ];

  $options = [
    "http" => [
      "header" => "Content-type: application/json\r\n",
      "method" => "POST",
      "content" => json_encode($event)
    ]
  ];

  $context = stream_context_create($options);
  $result = file_get_contents($url, false, $context);

  if ($result === false) {
    error_logger(new Exception("Erreur de notification SSE"));
  }

  return $result;
}
