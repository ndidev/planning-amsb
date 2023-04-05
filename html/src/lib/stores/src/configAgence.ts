import { writable } from "svelte/store";

import Notiflix from "notiflix";

import { fetcher } from "@app/utils";
import { HTTP } from "@app/errors";
import type { ServiceAgence } from "@app/types";

const endpoint = "config/agence";

const initial = null;

// Config - Agence
export const configAgence = writable<ServiceAgence[]>(initial, () => {
  fetchAll();

  document.addEventListener(`planning:${endpoint}`, fetchAll);

  return () => document.removeEventListener(`planning:${endpoint}`, fetchAll);
});

// FONCTIONS

async function fetchAll() {
  try {
    const lignes: ServiceAgence[] = await fetcher(endpoint);

    configAgence.set(lignes);
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
