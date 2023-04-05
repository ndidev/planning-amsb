import { writable } from "svelte/store";

import Notiflix from "notiflix";

import { fetcher, type FetcherOptions, jsonify, mapify } from "@app/utils";
import { HTTP } from "@app/errors";
import type { DBEventData } from "@app/types";

/**
 * Crée un store.
 *
 * @param endpoint         Endpoint de l'API lié au store
 * @param additionalEvents Événements SSE entraînant une mise à jour du store
 */
export function createFlatStore<T extends { id: string | number }>(
  endpoint: string,
  options: {
    params?: FetcherOptions["params"];
    satisfiesParams?: (data: T, params: URLSearchParams) => boolean;
    additionalEvents?: string[];
  } = {}
) {
  let {
    params = {},
    satisfiesParams = (data: T, params: URLSearchParams) => true,
    additionalEvents = [],
  } = options;

  params = new URLSearchParams(params);

  const initial = null;

  let current: Map<T["id"], T> = initial;

  const { subscribe, set, update } = writable<Map<T["id"], T>>(initial, () => {
    if (!current) fetchAll();

    document.addEventListener(`planning:${endpoint}`, handleDBEvent);
    for (const event of additionalEvents) {
      document.addEventListener(`planning:${event}`, fetchAll);
    }

    return () => {
      document.removeEventListener(`planning:${endpoint}`, handleDBEvent);
      for (const event of additionalEvents) {
        document.removeEventListener(`planning:${event}`, () => fetchAll);
      }
    };
  });

  return {
    subscribe,
    get,
    create: _dbCreate,
    update: _dbUpdate,
    patch: _dbPatch,
    delete: _dbDelete,
    setParams,
    endpoint,
  };

  // FONCTIONS

  /**
   * Récupérer un item.
   *
   * @param id Identifiant de l'item
   */
  async function get(id: T["id"]) {
    try {
      return current?.get(id) || (await fetcher(`${endpoint}/${id}`));
    } catch (err: unknown) {
      const error = err as HTTP.Error | Error;
      if (error instanceof HTTP.ResponseError) {
        Notiflix.Notify.failure(error.message);
      } else {
        Notiflix.Notify.failure("Erreur");
        console.error(error);
      }

      return null;
    }
  }

  /**
   * Créer un item.
   *
   * @param data Données de l'item
   */
  async function _dbCreate(data: T, params: FetcherOptions["params"] = {}) {
    const item: T = await fetcher(endpoint, {
      requestInit: {
        method: "POST",
        body: JSON.stringify(data),
      },
      params,
    });

    _create(item);

    return item;
  }

  /**
   * Mettre à jour un item.
   *
   * @param data Données de l'item
   */
  async function _dbUpdate(data: T, params: FetcherOptions["params"] = {}) {
    const item: T = await fetcher(`${endpoint}/${data.id}`, {
      requestInit: {
        method: "PUT",
        body: JSON.stringify(data),
      },
      params,
    });

    _update(item);

    return item;
  }

  /**
   * Mettre à jour certaines données d'un item.
   *
   * @param id   Identifiant de l'item
   * @param data Données de l'item
   */
  async function _dbPatch(
    id: T["id"],
    data: Partial<T>,
    params: FetcherOptions["params"] = {}
  ) {
    const item: T = await fetcher(`${endpoint}/${id}`, {
      requestInit: {
        method: "PATCH",
        body: JSON.stringify(data),
      },
      params,
    });

    _update(item);

    return item;
  }

  /**
   * Supprimer un item.
   *
   * @param id Identifiant de l'item
   */
  async function _dbDelete(id: T["id"], params: FetcherOptions["params"] = {}) {
    await fetcher(`${endpoint}/${id}`, {
      requestInit: {
        method: "DELETE",
      },
      params,
    });

    _delete(id);
  }

  /**
   * Mettre à jour les paramètres du store.
   *
   * @param params Paramètres de requête
   */
  function setParams(_params: FetcherOptions["params"] = {}) {
    _params = new URLSearchParams(_params);

    if (params.toString() !== _params.toString()) {
      params = _params;
      fetchAll();
    }
  }

  /**
   * Gestionnaire d'événement appelé lorsqu'une modification
   * de la base de données a été notifiée.
   *
   * Vérification du contenu de l'événement,
   * puis appel de la fonction appropriée.
   */
  function handleDBEvent(event: CustomEvent<DBEventData<T>>) {
    // Événement générique
    if (!event.detail) {
      fetchAll();
      return;
    }

    const { type, id, data } = event.detail;

    // En fonction d'un événement particulier
    try {
      switch (type) {
        case "create":
          _create(data);
          break;

        case "update":
        case "patch":
          if (data) {
            _update(data);
          } else {
            fetchAll();
          }
          break;

        case "delete":
          _delete(id);
          break;

        default:
          fetchAll();
          break;
      }
    } catch (error) {
      console.error(error);
      fetchAll();
    }
  }

  /**
   * Récupérer toutes les données.
   */
  async function fetchAll() {
    try {
      const items = mapify<T>(await fetcher(endpoint, { params }));

      // Si la valeur n'a pas changé, ne pas mettre à jour
      if (jsonify(current) === jsonify(items)) return;

      set(items);
      current = items;
    } catch (err: unknown) {
      const error = err as HTTP.Error | Error;
      if (error instanceof HTTP.ResponseError) {
        Notiflix.Notify.failure(error.message);
      } else {
        Notiflix.Notify.failure("Erreur");
        console.error(error);
      }
    }
  }

  /**
   * Créer un item.
   *
   * @param item Données de la ressource
   */
  function _create(item: T) {
    // Si l'item ne correspond pas aux paramètres de requête, ne pas mettre à jour le store
    if (!satisfiesParams(item, params as URLSearchParams)) return;

    update((items) => {
      if (!items) return;

      items.set(item.id, item);

      return (current = items);
    });
  }

  /**
   * Mettre à jour un item.
   *
   * @param item Données de l'item
   */
  function _update(item: T) {
    // Si l'item ne correspond pas aux paramètres de requête, ne pas mettre à jour le store
    if (
      !current?.get(item.id) &&
      !satisfiesParams(item, params as URLSearchParams)
    )
      return;

    // Si l'item mis à jour ne correspond plus aux paramètres de requête, le supprimer du store
    if (
      current?.get(item.id) &&
      !satisfiesParams(item, params as URLSearchParams)
    ) {
      _delete(item.id);
      return;
    }

    // Si tout OK ci-dessus, mettre à jour
    update((items) => {
      if (!items) return;

      items.set(item.id, item);

      return (current = items);
    });
  }

  /**
   * Supprimer un item.
   *
   * @param id ID de l'item
   */
  function _delete(id: T["id"]) {
    update((items) => {
      if (!items) return;

      items.delete(id);

      return (current = items);
    });
  }
}
