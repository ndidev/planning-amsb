import { writable } from "svelte/store";
import type { Writable } from "svelte/store";

import { fetcher } from "@app/utils";

const localStorageKey = "stores/pays";

const initial = JSON.parse(localStorage.getItem(localStorageKey) || "false");

// Config - pays
export const pays: Writable<Pays[]> = writable(initial, () => {
  async function recupererInfos() {
    const lignes: Pays[] = await fetcher("pays");

    pays.set(lignes);

    localStorage.setItem(localStorageKey, JSON.stringify(lignes));
  }

  recupererInfos();

  document.addEventListener("planning:pays", recupererInfos);

  return () => {
    document.removeEventListener("planning:pays", recupererInfos);
  };
});
