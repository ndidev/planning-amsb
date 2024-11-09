<!-- 
  @component
  
  Établit une connexion au server SSE.
 
  Lorsqu'une mise à jour de la base de données est efectuée par un client,
  un message est envoyé par le serveur SSE à tous les clients
  ayant souscrits au module concerné (`{{name},{type},{id_ressource_modifiee},{data_ressource}}`).
  Cette fonction envoie un événement correspondant au message reçu,
  de la forme `planning:{name}`, sur l'élément `document`.
 
  Exemple :
  message reçu : `{"name":"bois/rdvs","type":"update","id":34125,"data":{data}}`
  événement envoyé : `new CustomEvent("planning:bois/rdvs")`
 
  Usage :
  ```tsx
  <ConnexionSSE
    subscriptions: string[] = []  // Liste des modules souscrits
  />
  ``` 
 -->
<script lang="ts">
  import { onMount, onDestroy } from "svelte";

  import { appURLs } from "@app/utils";
  import { v4 as uuid } from "uuid";
  import type { DBEventData } from "@app/types";

  export let subscriptions: string[] = [];

  let source: EventSource = null;
  let isReconnect = false;

  /**
   * @param subscriptions Liste des modules souscrits
   */
  function startSseConnection(subscriptions: string[]) {
    // Création d'un identifiant unique pour la connexion
    const id = uuid();
    sessionStorage.setItem("sseId", id);

    let pendingUpdates = new Set<string>();

    const url = new URL(appURLs.sse);

    const params: Record<string, string> = {
      id,
      subs: subscriptions.join(","),
      apiKey: new URLSearchParams(location.search).get("api_key") || "",
    };

    url.search = new URLSearchParams(params).toString();

    source = new EventSource(url, { withCredentials: true });

    source.onopen = (event) => {
      // console.debug(
      //   "SSE : Connexion établie (souscriptions : ",
      //   subscriptions.join(", "),
      //   ")"
      // );

      if (isReconnect) {
        // console.debug("SSE : Connexion rétablie");
        document.dispatchEvent(new CustomEvent(`planning:sse-reconnect`));
        isReconnect = false;
      }
    };

    source.onerror = (error) => {
      if (source.readyState === source.CONNECTING) {
        console.warn("SSE : Connexion perdue, nouvelle tentative...");
        isReconnect = true;
      }

      if (source.readyState === source.CLOSED) {
        console.error("SSE : Connexion perdue, redémarrage...");
        restartSseConnection();
        isReconnect = true;
      }
    };

    source.onmessage = (event) => {};

    source.addEventListener("close", (event) => {
      closeSseConnection();
      console.warn("SSE : connexion fermée à la demande du serveur");
    });

    source.addEventListener("db", (dbEvent: MessageEvent<string>) => {
      const data: DBEventData<any> = JSON.parse(dbEvent.data);

      // N'envoyer l'événement que si la page est visible actuellement,
      // sinon, mettre l'événement en attente
      if (document.visibilityState === "visible") {
        applyUpdate(data);
      } else {
        pendingUpdates.add(data.name);
      }
    });

    // Envoi des événements en attente lorsque la page redevient visible
    document.addEventListener("visibilitychange", () => {
      if (document.visibilityState === "visible") {
        // console.debug("SSE : Page visible, envoi des événements en attente");
        applyPendingUpdates(pendingUpdates);
      }
    });
  }

  function closeSseConnection() {
    source.close();
  }

  function restartSseConnection() {
    closeSseConnection();
    startSseConnection(subscriptions);
  }

  function applyUpdate(data: DBEventData<any>) {
    document.dispatchEvent(
      new CustomEvent(`planning:${data.name}`, { detail: data })
    );
  }

  function applyPendingUpdates(pendingUpdates: Set<string>) {
    pendingUpdates.forEach((name) => {
      document.dispatchEvent(new CustomEvent(`planning:${name}`));
      pendingUpdates.delete(name);
    });
  }

  onMount(() => {
    startSseConnection(subscriptions);
  });

  onDestroy(() => {
    closeSseConnection();
  });
</script>
