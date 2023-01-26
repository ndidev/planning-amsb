import { writable } from "svelte/store";
import type { Writable } from "svelte/store";

import { fetcher } from "@app/utils";

const empty: Record<string, ConfigPDF[]> = {
  bois: [],
  vrac: [],
};

const localStorageKey = "stores/configsPdf";

const initial = JSON.parse(localStorage.getItem(localStorageKey) || "false");

// Config - PDF
export const configsPdf: Writable<ConfigPDF[]> = writable(initial, () => {
  async function recupererInfos() {
    const configs: ConfigPDF[] = await fetcher("config/pdf");

    const updated = structuredClone(empty);

    for (const config of configs) {
      updated[config.module].push(config);
    }

    configsPdf.set(updated);

    localStorage.setItem(localStorageKey, JSON.stringify(updated));
  }

  recupererInfos();

  document.addEventListener("planning:config/pdf", recupererInfos);

  return () =>
    document.removeEventListener("planning:config/pdf", recupererInfos);
});
