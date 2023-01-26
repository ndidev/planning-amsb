<?php

require_once __DIR__ . "/../../bootstrap.php";

/**
 * Notifier le serveur SSE d'une modification à la base de données.
 * 
 * @param string     $module Module concerné par la modification.
 * @param string     $type   Type de modification.
 * @param int|string $id     id de la ressource modifiée.
 * 
 * @return string|false 
 */
function notify_sse(string $module, string $type, int|string $id): string|false
{
  $host = $_ENV["SSE_HOST"];
  $port = $_ENV["SSE_PORT"];
  $url = "http://$host:$port";

  $data = [
    "module" => $module,
    "type" => $type,
    "id" => $id
  ];

  $options = [
    "http" => [
      "header" => "Content-type: application/json\r\n",
      "method" => "POST",
      "content" => json_encode($data)
    ]
  ];

  $context = stream_context_create($options);
  $result = file_get_contents($url, false, $context);

  if ($result === false) {
    error_logger(new Exception("Erreur de notification SSE"));
  }

  return $result;
}
