import { writable } from "svelte/store";

import { fetcher } from "@app/utils";
import type { Pays } from "@app/types";

const endpoint = "pays";

const initial = [];

export const pays = writable<Pays[]>(initial, () => {
  fetchAll();

  document.addEventListener(`planning:${endpoint}`, fetchAll);

  return () => {
    document.removeEventListener(`planning:${endpoint}`, fetchAll);
  };
});

// FONCTIONS

async function fetchAll() {
  const lignes: Pays[] = await fetcher(endpoint);

  pays.set(lignes);
}
