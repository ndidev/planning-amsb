import http from "node:http";
import { env } from "node:process";
import { connections } from "./stores";
import type { DBEvent } from "../types";

const UPDATES_PORT = parseInt(env["SSE_UPDATES_PORT"] as string);

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
    const events: DBEvent[] = JSON.parse(body);
    response.end();

    const isCloseEvent = (event: DBEvent) =>
      event.name === "admin/sessions" && event.type === "close";

    const normalEvents = events.filter((event) => !isCloseEvent(event));
    const closeEvents = events.filter((event) => isCloseEvent(event));

    const normalEventsHandled = new Map<DBEvent, Promise<void>>();

    normalEvents.forEach((event) => {
      normalEventsHandled.set(
        event,
        new Promise((resolve) => notifyEvent(event, resolve))
      );

      // En cas de mise à jour d'un compte utilisateur par l'admin ou l'utilisateur lui-même,
      // envoi de la mise à jour au compte concerné (événement "user")
      if (event.name === "admin/users") {
        // Filtrer les données sensibles
        const userEvent = {
          ...event,
          name: "user",
          data: event.data && {
            uid: event.id,
            login: event.data.login,
            nom: event.data.nom,
            roles: event.data.roles,
            statut: event.data.statut,
          },
        } satisfies DBEvent;

        normalEventsHandled.set(
          userEvent,
          new Promise((resolve) => notifyImpactedUser(userEvent, resolve))
        );
      }
    });

    normalEventsHandled.size > 0 &&
      Promise.all(normalEventsHandled.values()).then(() => {
        closeEvents.forEach((event) => {
          // En cas de déconnexion (ex: utilisateur désactivé/bloqué), clôturer la connexion
          if (String(event.id).startsWith("uid:")) {
            const uid = String(event.id).substring(4);
            closeConnectionsForUser(uid);
          }
          if (String(event.id).startsWith("sid:")) {
            const sid = String(event.id).substring(4);
            closeConnectionForSession(sid);
          }
        });
      });
  });
}

/**
 * Notifie les clients concernés d'un événement.
 *
 * @param event Événement à notifier.
 */
function notifyEvent(event: DBEvent, markEventHandled: () => void) {
  const origin = event.origin;
  delete event.origin;

  const allConnectionsNotified: Promise<void>[] = [];

  // Envoi de l'événement/notification aux clients concernés
  connections.forEach((connection) => {
    if (
      connection.subscriptions.includes(event.name) &&
      origin !== connection.id
    ) {
      allConnectionsNotified.push(
        new Promise((resolve) => {
          connection.response.write(`event: db\n`);
          connection.response.write(`data: ${JSON.stringify(event)}\n\n`, () =>
            resolve()
          );
        })
      );
    }
  });

  Promise.all(allConnectionsNotified).then(markEventHandled);
}

/**
 * Notifies the impacted user by sending a server-sent event (SSE) to all active connections
 * associated with the user ID from the given database event.
 *
 * @param event The database event containing the user ID and other relevant information.
 */
function notifyImpactedUser(event: DBEvent, markEventHandled: () => void) {
  const allUserConnectionsNotified: Promise<void>[] = [];

  [...connections]
    .filter((connection) => connection.userId === event.id)
    .forEach((connection) => {
      allUserConnectionsNotified.push(
        new Promise((resolve) => {
          connection.response.write(`event: db\n`);
          connection.response.write(`data: ${JSON.stringify(event)}\n\n`, () =>
            resolve()
          );
        })
      );
    });

  Promise.all(allUserConnectionsNotified).then(markEventHandled);
}

/**
 * Clôture toutes les connexions pour un utilisateur donné.
 *
 * @param userId Identifiant de l'utilisateur.
 */
function closeConnectionsForUser(userId: string) {
  [...connections].forEach((connection) => {
    if (connection.userId === userId) {
      connection.request.destroy();
      connections.delete(connection);
    }
  });
}

/**
 * Clôture la connexion pour une session donnée.
 *
 * @param sessionId Identifiant de la session.
 */
function closeConnectionForSession(sessionId: string) {
  const connection = [...connections].find(
    ({ sessionId: sid }) => sid === sessionId
  );
  if (connection) {
    connection.request.destroy();
    connections.delete(connection);
  }
}
