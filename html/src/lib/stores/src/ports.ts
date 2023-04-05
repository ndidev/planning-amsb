import { writable } from "svelte/store";

import Notiflix from "notiflix";

import { fetcher } from "@app/utils";
import { HTTP } from "@app/errors";
import type { Port } from "@app/types";

const endpoint = "ports";

const initial = [];

// Config - Ports
export const ports = writable<Port[]>(initial, () => {
  fetchAll();

  document.addEventListener(`planning:${endpoint}`, fetchAll);

  return () => {
    document.removeEventListener(`planning:${endpoint}`, fetchAll);
  };
});

// FONCTIONS

async function fetchAll() {
  try {
    const lignes: Port[] = await fetcher(endpoint);

    ports.set(lignes);
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
