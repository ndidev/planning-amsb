import { writable } from "svelte/store";
import type { Writable } from "svelte/store";

import { fetcher } from "@app/utils";

const localStorageKey = "stores/ports";

const initial =
  JSON.parse(localStorage.getItem(localStorageKey) || "false") || [];

// Config - ports
export const ports: Writable<Port[]> = writable(initial, () => {
  async function recupererInfos() {
    const lignes: Port[] = await fetcher("ports");

    ports.set(lignes);

    localStorage.setItem(localStorageKey, JSON.stringify(lignes));
  }

  recupererInfos();

  document.addEventListener("planning:ports", recupererInfos);

  return () => {
    document.removeEventListener("planning:ports", recupererInfos);
  };
});
