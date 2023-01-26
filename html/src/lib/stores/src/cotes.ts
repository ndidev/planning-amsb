import { writable } from "svelte/store";
import type { Writable } from "svelte/store";

import { fetcher } from "@app/utils";

const localStorageKey = "stores/cotes";

const initial = JSON.parse(localStorage.getItem(localStorageKey) || "false");

// Config - Cotes
export const cotes: Writable<Cote[]> = writable(initial, () => {
  async function recupererInfos() {
    const lignes: Cote[] = await fetcher("config/cotes");

    cotes.set(lignes);

    localStorage.setItem(localStorageKey, JSON.stringify(lignes));
  }

  recupererInfos();

  document.addEventListener("planning:config/cotes", recupererInfos);

  return () => {
    document.removeEventListener("planning:config/cotes", recupererInfos);
  };
});
