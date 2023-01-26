import { writable } from "svelte/store";
import type { Writable } from "svelte/store";

import { fetcher } from "@app/utils";

const localStorageKey = "stores/servicesAgence";

const initial = JSON.parse(localStorage.getItem(localStorageKey) || "false");

// Config - Agence
export const servicesAgence: Writable<ServiceAgence[]> = writable(
  initial,
  () => {
    async function recupererInfos() {
      const lignes: ServiceAgence[] = await fetcher("config/agence");

      servicesAgence.set(lignes);

      localStorage.setItem(localStorageKey, JSON.stringify(lignes));
    }

    recupererInfos();

    document.addEventListener("planning:config/agence", recupererInfos);

    return () =>
      document.removeEventListener("planning:config/agence", recupererInfos);
  }
);
