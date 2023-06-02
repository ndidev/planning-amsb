import { writable } from "svelte/store";

import Notiflix from "notiflix";

import { fetcher } from "@app/utils";
import { HTTP } from "@app/errors";
import type { Pays } from "@app/types";

const endpoint = "pays";

const initial = [];

// Config - Pays
export const pays = writable<Pays[]>(initial, () => {
  fetchAll();

  document.addEventListener(`planning:${endpoint}`, fetchAll);

  return () => {
    document.removeEventListener(`planning:${endpoint}`, fetchAll);
  };
});

// FONCTIONS

async function fetchAll() {
  try {
    const lignes: Pays[] = await fetcher(endpoint);

    pays.set(lignes);
  } catch (err: unknown) {
    const error = err as HTTP.Error | Error;
    console.error(error);

    if (
      error instanceof HTTP.ResponseError &&
      !(error instanceof HTTP.Unauthorized)
    ) {
      Notiflix.Notify.failure(error.message);
    } else {
      Notiflix.Notify.failure("Erreur");
      console.error(error);
    }
  }
}
