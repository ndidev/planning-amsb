import { env } from "@app/utils";
import { AuthException } from "@app/exceptions";

/**
 * Wrapper pour la fonction `fetch`.
 *
 * @param endpoint Endpoint de l'API
 * @param params   Optionnel. Paramètres de la requête
 * @param options  Optionnel. Options de la fonction fetch
 *
 * @returns Objet (`JSON.parse()`) de la ressource demandée
 */
export async function fetcher(
  endpoint: string,
  params: any = {},
  options: RequestInit = {}
): Promise<any> {
  const url = new URL(env.api);
  url.pathname += endpoint;

  url.search = new URLSearchParams(params).toString();

  const apiKey = new URLSearchParams(location.search).get("api_key");
  const headers = options.headers || {};

  if (apiKey) {
    headers["X-API-Key"] = apiKey;
  }

  options.headers = headers;

  const reponse = await fetch(url, options);

  if (!reponse.ok) {
    switch (reponse.status) {
      case 401:
        throw new AuthException(reponse);
      default:
        throw new Error(`${reponse.status} : ${reponse.statusText}`);
    }
  }

  return await reponse.json();
}
