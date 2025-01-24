import { writable } from "svelte/store";

import Notiflix from "notiflix";

import {
  fetcher,
  type FetcherOptions,
  jsonify,
  mapify,
  ReadyPromise,
} from "@app/utils";
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
  itemTemplate: T = null,
  options: {
    satisfiesParams?: (data: T, searchParams: URLSearchParams) => boolean;
    additionalEvents?: string[];
  } = {}
) {
  let {
    satisfiesParams = (data: T, searchParams: URLSearchParams) => true,
    additionalEvents = [],
  } = options;

  let searchParams = new URLSearchParams();

  const initial = null;

  let current: Map<T["id"], T> = initial;

  let readyPromise: ReadyPromise = new ReadyPromise();

  const { subscribe, set, update } = writable<Map<T["id"], T>>(initial, () => {
    fetchAll();

    document.addEventListener(`planning:${endpoint}`, handleDBEvent);
    document.addEventListener(`planning:sse-reconnect`, fetchAll);
    for (const event of additionalEvents) {
      document.addEventListener(`planning:${event}`, fetchAll);
    }

    return () => {
      document.removeEventListener(`planning:${endpoint}`, handleDBEvent);
      document.removeEventListener(`planning:sse-reconnect`, fetchAll);
      for (const event of additionalEvents) {
        document.removeEventListener(`planning:${event}`, () => fetchAll);
      }
    };
  });

  return {
    subscribe,
    get,
    getAll: fetchAll,
    new: _new,
    cancel: _cancel,
    create: _dbCreate,
    update: _dbUpdate,
    patch: _dbPatch,
    delete: _dbDelete,
    getTemplate: () => structuredClone(itemTemplate),
    setSearchParams,
    endpoint,
    getReadyState,
  };

  // FONCTIONS

  function resetReady() {
    readyPromise = new ReadyPromise();
  }

  /**
   * Retrieves the promise representing the readiness state.
   *
   * @returns A promise that resolves when the store data is fetched.
   */
  function getReadyState() {
    return readyPromise.promise;
  }

  /**
   * Récupérer toutes les données.
   */
  async function fetchAll() {
    try {
      if (readyPromise.isPending === false) {
        resetReady();
      }

      const items = mapify<T>(await fetcher(endpoint, { searchParams }));

      // Si la valeur n'a pas changé, ne pas mettre à jour
      if (jsonify(current) === jsonify(items)) {
        readyPromise.resolve();
        return current;
      }

      set(items);
      current = items;

      readyPromise.resolve();
      return items;
    } catch (err: unknown) {
      const error = err as HTTP.Error | Error;

      if (
        error instanceof HTTP.ResponseError &&
        !(error instanceof HTTP.Unauthorized)
      ) {
        Notiflix.Notify.failure(error.message);
      } else {
        Notiflix.Notify.failure("Erreur");
        console.error(error);
      }

      readyPromise.reject(error);
      throw error;
    }
  }

  /**
   * Récupérer un item.
   *
   * @param id Identifiant de l'item
   */
  async function get(id: T["id"]) {
    try {
      const storedItem = current?.get(id);

      if (storedItem) {
        return storedItem;
      }

      const fetchedItem = await fetcher<T>(`${endpoint}/${id}`);

      update((items) => {
        if (!items) {
          items = new Map();
        }

        items.set(fetchedItem.id, fetchedItem);

        return (current = items);
      });

      return fetchedItem;
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
   * Mettre à jour les paramètres du store.
   *
   * @param params Paramètres de requête
   */
  function setSearchParams(newParams: FetcherOptions["searchParams"] = {}) {
    const newSearchParams = new URLSearchParams(newParams);

    if (searchParams.toString() !== newSearchParams.toString()) {
      set(initial);
      current = initial;
      searchParams = newSearchParams;
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
   * Ajouter un item.
   */
  function _new() {
    if (!itemTemplate) return null;

    const tmpItem = structuredClone(itemTemplate);
    tmpItem.id = Math.random();

    update((items) => {
      items.set(tmpItem.id, tmpItem);

      return items;
    });

    return tmpItem;
  }

  /**
   * Annuler un ajout d'item.
   *
   * @param id Identifiant de l'item'
   */
  function _cancel(id: T["id"]) {
    _delete(id);
  }

  /**
   * Créer un item.
   *
   * @param data Données de l'item
   */
  async function _dbCreate(
    data: T,
    searchParams: FetcherOptions["searchParams"] = {}
  ) {
    const item: T = await fetcher(endpoint, {
      requestInit: {
        method: "POST",
        body: JSON.stringify(data),
      },
      searchParams,
    });

    // Suppression de l'item temporaire (si existant)
    _delete(data.id);

    // Mise à jour du store avec l'item créé
    _create(item);

    return item;
  }

  /**
   * Mettre à jour un item.
   *
   * @param data Données de l'item
   */
  async function _dbUpdate(
    data: T,
    searchParams: FetcherOptions["searchParams"] = {}
  ) {
    const item: T = await fetcher(`${endpoint}/${data.id}`, {
      requestInit: {
        method: "PUT",
        body: JSON.stringify(data),
      },
      searchParams,
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
    searchParams: FetcherOptions["searchParams"] = {}
  ) {
    const item: T = await fetcher(`${endpoint}/${id}`, {
      requestInit: {
        method: "PATCH",
        body: JSON.stringify(data),
      },
      searchParams,
    });

    _update(item);

    return item;
  }

  /**
   * Supprimer un item.
   *
   * @param id Identifiant de l'item
   */
  async function _dbDelete(
    id: T["id"],
    searchParams: FetcherOptions["searchParams"] = {}
  ) {
    await fetcher(`${endpoint}/${id}`, {
      requestInit: {
        method: "DELETE",
      },
      searchParams,
    });

    _delete(id);
  }

  /**
   * Créer un item.
   *
   * @param item Données de la ressource
   */
  function _create(item: T) {
    // Si l'item ne correspond pas aux paramètres de requête, ne pas mettre à jour le store
    if (!satisfiesParams(item, searchParams)) return;

    update((items) => {
      if (!items) {
        items = new Map();
      }

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
    if (!current?.get(item.id) && !satisfiesParams(item, searchParams)) return;

    // Si l'item mis à jour ne correspond plus aux paramètres de requête, le supprimer du store
    if (current?.get(item.id) && !satisfiesParams(item, searchParams)) {
      _delete(item.id);
      return;
    }

    // Si tout OK ci-dessus, mettre à jour
    update((items) => {
      if (!items) {
        items = new Map();
      }

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
