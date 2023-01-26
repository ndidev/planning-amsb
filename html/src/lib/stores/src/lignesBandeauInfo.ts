import { writable } from "svelte/store";
import type { Writable } from "svelte/store";

import { fetcher } from "@app/utils";

const empty: Record<string, LigneBandeauInfo[]> = {
  bois: [],
  vrac: [],
  consignation: [],
  chartering: [],
};

const localStorageKey = "stores/lignesBandeauInfo";

const initial = JSON.parse(localStorage.getItem(localStorageKey) || "false");

// Config - BandeauInfo
export const lignesBandeauInfo: Writable<LigneBandeauInfo[]> = writable(
  initial,
  () => {
    async function recupererInfos() {
      const lignes: LigneBandeauInfo[] = await fetcher("config/bandeau-info");

      const updated = structuredClone(empty);

      for (const ligne of lignes) {
        updated[ligne.module].push(ligne);
      }

      lignesBandeauInfo.set(updated);

      localStorage.setItem(localStorageKey, JSON.stringify(updated));
    }

    recupererInfos();

    document.addEventListener("planning:config/bandeau-info", recupererInfos);

    return () => {
      document.removeEventListener(
        "planning:config/bandeau-info",
        recupererInfos
      );
    };
  }
);
