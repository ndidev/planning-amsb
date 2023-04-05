import http from "node:http";
import { env } from "node:process";
import { connections } from "./stores";

const UPDATES_PORT = parseInt(env["UPDATES_PORT"] as string);

/**
 * Serveur "DBEvent".
 *
 * Reçoit les notifications de mise à jour de la base de données
 * et transmet les mises à jour à chaque coonnexion client concernée.
 */
const updatesServer = http.createServer(dbEventsListener);
updatesServer.listen(UPDATES_PORT, () => {
  console.log(
    `[${new Date().toISOString()}] Updates server listening on port ${UPDATES_PORT}...`
  );
});

/**
 * Écouteur de notifications provenant du serveur PHP.
 *
 * Chaque notification est transférée aux connexions concernées.
 */
function dbEventsListener(
  request: http.IncomingMessage,
  response: http.ServerResponse
) {
  /**
   * Corps de la requête.
   */
  let body: string = "";

  request.on("data", (chunk: string) => {
    body += chunk;
  });

  request.on("end", () => {
    const event: DBEventData = JSON.parse(body);
    response.end();

    const origin = event.origin;
    delete event.origin;

    // Envoi de l'événement/notification aux clients concernés
    connections.forEach((connection) => {
      if (
        connection.subscriptions.includes(event.name) &&
        origin !== connection.id
      ) {
        connection.response.write(`event: db\n`);
        connection.response.write(`data: ${JSON.stringify(event)}\n\n`);
      }
    });

    // En cas de mise à jour d'un compte utilisateur par l'admin ou l'utilisateur lui-même,
    // envoi de la mise à jour au compte concerné (événement "user")
    if (event.name === "admin/users") {
      // Envoi à l'utilisateur concerné
      event.name = "user";
      if (event.data) {
        const data = {
          login: event.data.login,
          nom: event.data.nom,
          roles: event.data.roles,
          statut: event.data.statut,
        };
        event.data = data;
      }

      [...connections]
        .filter((connection) => connection.userId === event.id)
        .forEach((connection) => {
          connection.response.write(`event: db\n`);
          connection.response.write(`data: ${JSON.stringify(event)}\n\n`);
        });
    }
  });
}
