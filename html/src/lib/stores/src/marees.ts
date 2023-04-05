import { writable, derived } from "svelte/store";

import Notiflix from "notiflix";

import { fetcher } from "@app/utils";
import { HTTP } from "@app/errors";
import type { DBEventData, Maree } from "@app/types";

const endpoint = "marees";

const initial = null;

// Marées
export const marees = writable<Maree[]>(initial, () => {
  fetchAll();

  document.addEventListener(`planning:${endpoint}`, handleDBEvent);

  return () => {
    document.removeEventListener(`planning:${endpoint}`, handleDBEvent);
  };
});

export const mareesAnnees = derived(marees, ($marees) => {
  if ($marees) {
    return new Set($marees.map(({ date }) => date.slice(0, 4)));
  } else {
    // Données en cours de chargement
    return null;
  }
});

// FONCTIONS

/**
 * Gestionnaire d'événement appelé lorsqu'une modification
 * de la base de données a été notifiée.
 *
 * Vérification du contenu de l'événement,
 * puis appel de la fonction appropriée.
 */
function handleDBEvent(event: CustomEvent<DBEventData<Maree>>) {
  // Événement générique
  if (!event.detail) {
    fetchAll();
    return;
  }

  const { type, id, data } = event.detail;

  // En fonction d'un événement particulier
  switch (type) {
    case "create":
      fetchAll();
      break;

    case "delete":
      _delete(id as number);
      break;

    default:
      fetchAll();
      break;
  }
}

/**
 * Récupérer toutes les données.
 */
async function fetchAll() {
  try {
    const lignes: Maree[] = await fetcher(endpoint);

    marees.set(lignes);
  } catch (err: unknown) {
    const error = err as HTTP.Error | Error;
    if (error instanceof HTTP.ResponseError) {
      Notiflix.Notify.failure(error.message);
    } else {
      Notiflix.Notify.failure("Erreur");
      console.error(error);
    }
  }
}

/**
 * Supprimer les marées d'une année.
 *
 * @param annee Années des marées
 */
function _delete(annee: number) {
  marees.update((lignes) => {
    lignes = lignes.filter(({ date }) => date.slice(0, 4) !== annee.toString());

    return lignes;
  });
}
