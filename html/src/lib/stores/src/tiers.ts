import { writable, derived } from "svelte/store";
import type { Writable } from "svelte/store";

import { fetcher } from "@app/utils";

const localStorageKey = "stores/tiers";

const initial = JSON.parse(localStorage.getItem(localStorageKey) || "false");

// Tiers
export const tiers: Writable<Tiers[]> = writable(initial, () => {
  async function recupererInfos() {
    const lignes: Tiers[] = await fetcher("tiers");

    const updated = lignes;

    tiers.set(updated);

    localStorage.setItem(localStorageKey, JSON.stringify(updated));
  }

  recupererInfos();

  document.addEventListener("planning:tiers", recupererInfos);

  return () => {
    document.removeEventListener("planning:tiers", recupererInfos);
  };
});

export const tiersModifiables = derived(tiers, ($tiers) =>
  $tiers.filter((tiers) => !tiers.non_modifiable)
);
