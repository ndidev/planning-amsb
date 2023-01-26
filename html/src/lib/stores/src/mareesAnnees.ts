import { writable } from "svelte/store";
import type { Writable } from "svelte/store";

import { fetcher } from "@app/utils";

const localStorageKey = "stores/mareesAnnees";

const initial = JSON.parse(localStorage.getItem(localStorageKey) || "false");

export const mareesAnnees: Writable<string[]> = writable(initial, () => {
  async function recupererInfos() {
    const lignes: string[] = await fetcher("marees", { annees: true });

    // Tri par années décroissantes
    lignes.sort((a: string, b: string) => parseInt(b) - parseInt(a));

    mareesAnnees.set(lignes);

    localStorage.setItem(localStorageKey, JSON.stringify(lignes));
  }

  recupererInfos();

  document.addEventListener("planning:marees", recupererInfos);

  return () => {
    document.removeEventListener("planning:marees", recupererInfos);
  };
});
