import { writable } from "svelte/store";

import { fetcher } from "@app/utils";
import { User, type UserInfo } from "@app/auth";
import { HTTP } from "@app/errors";
import type { DBEventData } from "@app/types";

const endpoint = "user";

const initial =
  JSON.parse(localStorage.getItem(endpoint) || "null") || undefined;

// Utilisateur courant
export const currentUser = writable<User>(new User(initial), () => {
  fetchAll();

  document.addEventListener(`planning:${endpoint}`, handleDBEvent);

  return () =>
    document.removeEventListener(`planning:${endpoint}`, handleDBEvent);
});

// FONCTIONS

/**
 * Gestionnaire d'événement appelé lorsqu'une modification
 * de la base de données a été notifiée.
 *
 * Vérification du contenu de l'événement,
 * puis appel de la fonction appropriée.
 */
function handleDBEvent(event: CustomEvent<DBEventData<UserInfo>>) {
  // Événement générique
  if (!event.detail) {
    fetchAll();
    return;
  }

  const { type, id, data } = event.detail;
  // En fonction d'un événement particulier
  switch (type) {
    case "update":
      if (data) {
        currentUser.set(new User(data));
        localStorage.setItem(endpoint, JSON.stringify(data));
      } else {
        fetchAll();
      }
      break;

    case "delete":
      currentUser.set(new User());
      localStorage.clear();
      break;

    default:
      fetchAll();
      break;
  }
}

async function fetchAll() {
  try {
    const user: UserInfo = await fetcher(endpoint);

    currentUser.set(new User(user));

    localStorage.setItem(endpoint, JSON.stringify(user));
  } catch (err: unknown) {
    const error = err as HTTP.Error | Error;
    if (error instanceof HTTP.Unauthorized) {
      localStorage.clear();
      currentUser.set(new User());
    } else {
      console.error(error.message);
    }
  }
}
