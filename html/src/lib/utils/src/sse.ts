import { env } from "./environment";

/**
 * Établit une connexion au server SSE.
 *
 * Lorsqu'une mise à jour de la base de données est efectuée par un client,
 * un message est envoyé par le serveur SSE à tous les clients
 * ayant souscrits au module concerné (`{{module},{type},{id_ressource_modifiee}}`).
 * Cette fonction envoie un événement correspondant au message reçu,
 * de la forme `planning:{module}`, sur l'élément `document`.
 *
 * Exemple :
 * message reçu : `{"module":"bois","type":"update","id":34125}`
 * événement envoyé : `new Event("planning:bois")`
 *
 * @param subscriptions Liste des modules souscrits
 */
export function demarrerConnexionSSE(subscriptions: string[]): EventSource {
  /**
   * @type {Set<string>}
   */
  let pendingUpdates: Set<string> = new Set();

  const url = new URL(env.sse);

  const params = {
    subs: subscriptions.join(","),
  };

  url.search = new URLSearchParams(params).toString();

  const source = new EventSource(url);

  source.onopen = (event) => {
    console.log("SSE : Connexion établie");
  };

  source.onerror = (error) => {
    console.error("SSE : Échec");
  };

  source.onmessage = (event) => {};

  source.addEventListener("close", (event) => {
    source.close();
    console.warn("SSE : connexion fermée à la demande du serveur");
  });

  source.addEventListener("db", (dbEvent) => {
    const data: {
      module: string;
      type: string;
      id: number | string;
    } = JSON.parse(dbEvent.data);

    // N'envoyer l'événement que si la page est visible actuellement,
    // sinon, mettre l'événement en attente
    if (document.visibilityState === "visible") {
      document.dispatchEvent(new Event(`planning:${data.module}`));
    } else {
      pendingUpdates.add(data.module);
    }
  });

  // Envoi des événements en attente lorsque la page redevient visible
  document.addEventListener("visibilitychange", () => {
    if (document.visibilityState === "visible") {
      pendingUpdates.forEach((module) => {
        document.dispatchEvent(new Event(`planning:${module}`));
        pendingUpdates.delete(module);
      });
    }
  });

  return source;
}
