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
    subscriptions: string[] =[]  // Liste des modules souscrits
  />
  ``` 
 -->
<script lang="ts">
  import { onMount, onDestroy } from "svelte";

  import { appURLs } from "@app/utils";
  import { v4 as uuid } from "uuid";
  import type { DBEventData } from "@app/types";

  export let subscriptions: string[] = [];

  let source: EventSource;

  /**
   * @param subscriptions Liste des modules souscrits
   */
  function demarrerConnexionSSE(subscriptions: string[]): EventSource {
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

    let connected = false;

    const source = new EventSource(url, { withCredentials: true });

    source.onopen = (event) => {
      console.log("SSE : Connexion établie");
      connected = true;
    };

    source.onerror = (error) => {
      console.error("SSE : Échec");
    };

    source.onmessage = (event) => {};

    source.addEventListener("close", (event) => {
      source.close();
      connected = false;
      console.warn("SSE : connexion fermée à la demande du serveur");
    });

    source.addEventListener("db", (dbEvent: MessageEvent<string>) => {
      const data: DBEventData<any> = JSON.parse(dbEvent.data);

      // N'envoyer l'événement que si la page est visible actuellement,
      // sinon, mettre l'événement en attente
      if (document.visibilityState === "visible") {
        document.dispatchEvent(
          new CustomEvent(`planning:${data.name}`, { detail: data })
        );
      } else {
        pendingUpdates.add(data.name);
      }
    });

    // Envoi des événements en attente lorsque la page redevient visible
    document.addEventListener("visibilitychange", () => {
      if (document.visibilityState === "visible") {
        pendingUpdates.forEach((name) => {
          document.dispatchEvent(new CustomEvent(`planning:${name}`));
          pendingUpdates.delete(name);
        });
      }
    });

    return source;
  }

  onMount(() => {
    source = demarrerConnexionSSE(subscriptions);
  });

  onDestroy(() => {
    source.close();
  });
</script>
