import type { AppURLs } from "@app/types";

// TODO: erreurs personnalisées

declare global {
  interface URL {
    base: string;
  }
}

/**
 * BASE
 */
const BASE =
  import.meta.env.VITE_API_HOST ||
  window.location.protocol + "//" + window.location.host;

/**
 * API
 */
const api = new URL(`/api/`, BASE);

/**
 * AUTH
 */
const auth = new URL(`/auth/`, BASE);

/**
 * SERVER SIDE EVENTS
 */
const sse = new URL(`/sse/`, BASE);

/**
 * LOGOS
 */
const logos = new URL(`/logos/`, BASE);

/**
 * URLs de l'application.
 */
export const appURLs: Readonly<AppURLs> = Object.freeze({
  api,
  auth,
  sse,
  logos,
});
