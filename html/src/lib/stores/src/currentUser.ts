import { writable } from "svelte/store";
import type { Writable } from "svelte/store";

import { fetcher } from "@app/utils";
import { User, AccountStatus } from "@app/auth";
import { AuthException } from "@app/exceptions";

const localStorageKey = "stores/user";

const initial = JSON.parse(
  localStorage.getItem(localStorageKey) || "false"
) || { login: "", nom: "", roles: {} };

// Utilisateur courant
export const currentUser: Writable<User> = writable(new User(initial), () => {
  async function recupererInfos() {
    try {
      const user: {
        login: string;
        nom: string;
        roles: Roles;
        statut: AccountStatus;
      } = await fetcher("user");

      currentUser.set(new User(user));

      localStorage.setItem(localStorageKey, JSON.stringify(user));
    } catch (error: unknown) {
      if (error instanceof AuthException) {
        localStorage.removeItem(localStorageKey);
        location.href = "/";
      }
    }
  }

  recupererInfos();

  document.addEventListener("planning:user", recupererInfos);

  return () => document.removeEventListener("planning:user", recupererInfos);
});
