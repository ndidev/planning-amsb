// TODO: erreurs personnalisées

declare global {
  interface URL {
    base: string;
    prefix: string;
  }
}

/**
 * BASE
 */
const BASE = window.location.protocol + "//" + window.location.host;
const PREFIX = process.env.URL_PREFIX || "";
// const BASE = "http://localhost:8080";
// const PREFIX = ""; // process.env.URL_PREFIX || ""

/**
 * API
 */
const api = new URL(`${PREFIX}/api/`, BASE);
api.base = BASE;
api.prefix = PREFIX;

/**
 * AUTH
 */
const auth = new URL(`${PREFIX}/auth/`, BASE);
auth.base = BASE;
auth.prefix = PREFIX;

/**
 * SERVER SIDE EVENTS
 */
const sse = new URL(`${PREFIX}/sse`, BASE);
sse.base = BASE;
sse.prefix = PREFIX;

/**
 * LOGOS
 */
const logos = new URL(`${PREFIX}/logos/`, BASE);
auth.base = BASE;
auth.prefix = PREFIX;

/**
 * @property {string} base   Base de l'URL (https://{domaine.tld})
 * @property {string} prefix Préfixe (dossier initial) de l'application (https://{domaine.tld}/{prefix}/...)
 * @property {URL}    api    Objet `URL` de l'API générale
 * @property {URL}    auth   Objet `URL` de l'API d'authentification
 * @property {URL}    sse    Objet `URL` du serveur Server-Sent Events
 * @property {URL}    logos  Objet `URL` des logos
 */
export const env = Object.freeze({
  base: BASE,
  prefix: PREFIX,
  api,
  auth,
  sse,
  logos,
});
