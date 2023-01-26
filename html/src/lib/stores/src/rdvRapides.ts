import { writable } from "svelte/store";
import type { Writable } from "svelte/store";

import { fetcher } from "@app/utils";

const empty: Record<string, RdvRapideBois[]> = { bois: [] };

const localStorageKey = "stores/rdvRapides";

const initial = JSON.parse(localStorage.getItem(localStorageKey) || "false");

// Config - RdvRapides
export const rdvRapides: Writable<RdvRapideBois[]> = writable(initial, () => {
  async function recupererInfos() {
    const lignes: RdvRapideBois[] = await fetcher("config/rdvrapides");

    const updated = structuredClone(empty);

    for (const ligne of lignes) {
      updated[ligne.module].push(ligne);
    }

    rdvRapides.set(updated);

    localStorage.setItem(localStorageKey, JSON.stringify(updated));
  }

  recupererInfos();

  document.addEventListener("planning:config/rdvrapides", recupererInfos);

  return () => {
    document.removeEventListener("planning:config/rdvrapides", recupererInfos);
  };
});
