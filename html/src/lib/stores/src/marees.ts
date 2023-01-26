import { writable } from "svelte/store";
import type { Writable } from "svelte/store";

import { fetcher } from "@app/utils";

type Maree = {
  date: string;
  heure: string;
  te_cesson: number;
  te_bassin: number;
};

const localStorageKey = "stores/marees";

const initial = JSON.parse(localStorage.getItem(localStorageKey) || "false");

// Config - pays
export const marees: Writable<Maree[]> = writable(initial, () => {
  async function recupererInfos() {
    const lignes: Maree[] = await fetcher("marees");

    marees.set(lignes);

    localStorage.setItem(localStorageKey, JSON.stringify(lignes));
  }

  recupererInfos();

  document.addEventListener("planning:marees", recupererInfos);

  return () => {
    document.removeEventListener("planning:marees", recupererInfos);
  };
});
