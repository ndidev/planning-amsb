import { appURLs } from "@app/utils";
import { HTTP } from "@app/errors";
import type { AppURLs } from "@app/types";

/**
 * Wrapper pour la fonction `fetch`.
 *
 * @param endpoint             Endpoint de l'API
 * @param options              Options
 * @param options.prefix       Optionnel. Préfixe de l'URL. Défaut = "api"
 * @param options.accept       Optionnel. Type de réponse attendu (doit être une méthode valide de l'interface `Response`). Défaut = "json"
 * @param options.params       Optionnel. Paramètres de la requête (`searchParams`)
 * @param options.requestInit  Optionnel. Options de la fonction `fetch`
 *
 * @throws {HTTP.ResponseError}
 */
export async function fetcher<T>(
  endpoint: string | URL,
  options: FetcherOptions = {}
): Promise<T> {
  const {
    prefix = "api",
    accept = "json",
    searchParams = {},
    requestInit = {},
  } = options;

  const url =
    typeof endpoint === "string"
      ? new URL(endpoint, appURLs[prefix])
      : endpoint;

  for (const [name, value] of new URLSearchParams(searchParams)) {
    url.searchParams.append(name, value);
  }

  const defaultHeaders: HeadersInit = {
    "X-SSE-Connection": sessionStorage.getItem("sseId") || "",
    "X-API-Key": new URLSearchParams(location.search).get("api_key") || "",
  };

  requestInit.headers = { ...requestInit.headers, ...defaultHeaders };

  requestInit.credentials = "include";

  const response = await fetch(url, requestInit);

  if (!response.ok) {
    await HTTP.throwError(response);
  }

  // Si pas de contenu (statut 204), ne rien retourner
  if (response.status === 204) return;

  return response[accept]();
}

/**
 * Options de la fonction `fetcher`.
 */
export type FetcherOptions = {
  /**
   * Optionnel. Préfixe de l'URL (défaut = "api").
   */
  prefix?: keyof AppURLs;

  /**
   * Optionnel. Type de réponse attendu (doit être `Response` ou une méthode valide de l'interface `Response`).
   */
  accept?: "arrayBuffer" | "blob" | "formData" | "json" | "text";

  /**
   * Optionnel. Paramètres de la requête (`searchParams`).
   */
  searchParams?: URLSearchParams | { [name: string]: string };

  /**
   * Optionnel. Options de la fonction fetch.
   */
  requestInit?: RequestInit;
};
