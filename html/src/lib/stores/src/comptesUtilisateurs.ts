import { writable } from "svelte/store";
import type { Writable } from "svelte/store";

import { fetcher } from "@app/utils";

const localStorageKey = "stores/comptesUtilisateurs";

const initial = JSON.parse(localStorage.getItem(localStorageKey) || "false");

// Admin - Comptes
export const comptesUtilisateurs: Writable<CompteUtilisateur[]> = writable(
  initial,
  () => {
    async function recupererInfos() {
      const comptes: CompteUtilisateur[] = await fetcher("admin/users");

      comptesUtilisateurs.set(comptes);

      localStorage.setItem(localStorageKey, JSON.stringify(comptes));
    }

    recupererInfos();

    document.addEventListener("planning:admin/users", recupererInfos);

    return () =>
      document.removeEventListener("planning:admin/users", recupererInfos);
  }
);
