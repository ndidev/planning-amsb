import http from "node:http";
import { env } from "node:process";
import { authenticate } from "./auth";
import { connections } from "./stores";
import type { Connection } from "../types";

const CLIENTS_PORT = parseInt(env["CLIENTS_PORT"] as string);

/**
 * Serveur SSE.
 *
 * Reçoit les connexions clients et les enregistre.
 */
const sseServer = http.createServer(clientsListener);
sseServer.listen(CLIENTS_PORT, () => {
  console.log(
    `[${new Date().toISOString()}] SSE server listening on port ${CLIENTS_PORT}...`
  );
});

/**
 * Écouteur de connexions clients (navigateurs).
 *
 * Inscrit les connexions dans le tableau `connections`
 * et maintient la connexion au client via un ping régulier.
 */
async function clientsListener(
  request: http.IncomingMessage,
  response: http.ServerResponse
) {
  const userId = await authenticate(request);

  if (!userId) {
    response.statusCode = 401;
    response.setHeader(
      "Access-Control-Allow-Origin",
      request.headers.origin || "*"
    );
    response.setHeader("Access-Control-Allow-Credentials", "true");
    response.end();
    return;
  }

  const query = new URLSearchParams(request.url?.slice(1));
  const id = query.get("id") || "";
  const subscriptions = query.get("subs")?.split(",") || [];

  /**
   * Connexion du client.
   */
  const connection: Connection = {
    id,
    userId,
    request,
    response,
    subscriptions,
  };

  // Mise à jour des tableaux des connexions
  connections.add(connection);

  // Établissement de la connexion
  response.statusCode = 200;
  response.setHeader("Content-Type", "text/event-stream");
  response.setHeader("Cache-Control", "no-cache");
  response.setHeader("Connection", "keep-alive");
  response.setHeader(
    "Access-Control-Allow-Origin",
    request.headers.origin || "*"
  );
  response.setHeader("Access-Control-Allow-Credentials", "true");
  response.write("event: open\n");
  response.write("retry: 5000\n\n");

  // Ping régulier pour maintenir la connexion ouverte
  const pingTicker = 60; // secondes
  setInterval(() => {
    response.write(":ping\n\n");
  }, pingTicker * 1000);

  // Mise à jour du tableau des connexions actives
  // en cas de fermeture d'une connexion
  request.on("close", () => {
    connections.delete(connection);
  });
}
