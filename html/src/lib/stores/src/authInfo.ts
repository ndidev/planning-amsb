import { readable } from "svelte/store";

import { fetcher, appURLs } from "@app/utils";

import type { LoginInfo } from "@app/types";

export const authInfo = readable<LoginInfo>(
  {
    MAX_LOGIN_ATTEMPTS: 0,
    LONGUEUR_MINI_PASSWORD: 8,
  },
  (set) => {
    const url = new URL(appURLs.auth);
    url.pathname += "info";
    fetcher(url).then(set);
  }
);
