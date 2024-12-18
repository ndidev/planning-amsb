import { writable } from "svelte/store";

import Notiflix from "notiflix";

import { fetcher, type FetcherOptions } from "@app/utils";
import { HTTP } from "@app/errors";
import type { DBEventData, Maree } from "@app/types";

type ParamsMarees = {
  /** Date de début des marées. */
  debut?: string;
  /** Date de fin des marées. */
  fin?: string;
};

// Marées
export const marees = createStore();

function createStore() {
  const initial = null;
  const endpoint = "marees";
  let searchParams = new URLSearchParams();

  const { subscribe, set, update } = writable<Maree[]>(initial, () => {
    fetchAll();

    document.addEventListener(`planning:${endpoint}`, handleDBEvent);
    document.addEventListener(`planning:sse-reconnect`, fetchAll);

    return () => {
      document.removeEventListener(`planning:${endpoint}`, handleDBEvent);
      document.removeEventListener(`planning:sse-reconnect`, fetchAll);
    };
  });

  return {
    subscribe,
    create: _dbCreate,
    delete: _dbDelete,
    setSearchParams,
    endpoint,
  };

  // FONCTIONS

  /**
   * Créer un item.
   *
   * @param data Données de l'item
   */
  async function _dbCreate(
    data: FormData,
    params: FetcherOptions["searchParams"] = {}
  ) {
    const { annee } = await fetcher<{ annee: number }>(endpoint, {
      requestInit: {
        method: "POST",
        body: data,
      },
      searchParams: params,
    });

    mareesAnnees.update((annees) => {
      annees.push(annee.toString());
      annees.sort((a, b) => Number(b) - Number(a));

      return annees;
    });
  }

  /**
   * Supprimer un item.
   *
   * @param annee Identifiant de l'item
   */
  async function _dbDelete(
    annee: number,
    params: FetcherOptions["searchParams"] = {}
  ) {
    await fetcher(`${endpoint}/${annee}`, {
      requestInit: {
        method: "DELETE",
      },
      searchParams: params,
    });

    _delete(annee);
  }

  /**
   * Mettre à jour les paramètres du store.
   *
   * @param params Paramètres de requête
   */
  function setSearchParams(
    newParams: ParamsMarees = {},
    fetch: boolean = true
  ) {
    let newSearchParams = new URLSearchParams(newParams);

    if (searchParams.toString() !== newSearchParams.toString()) {
      set(initial);
      searchParams = newSearchParams;
    }

    if (fetch) {
      fetchAll();
    }
  }

  /**
   * Récupérer toutes les données.
   */
  async function fetchAll() {
    try {
      const lignes: Maree[] = await fetcher(endpoint, {
        searchParams: searchParams,
      });

      set(lignes);
    } catch (err: unknown) {
      const error = err as HTTP.Error | Error;
      console.error(error);

      if (
        error instanceof HTTP.ResponseError &&
        !(error instanceof HTTP.Unauthorized)
      ) {
        Notiflix.Notify.failure(error.message);
      } else {
        Notiflix.Notify.failure("Erreur");
        console.error(error);
      }
    }
  }

  /**
   * Gestionnaire d'événement appelé lorsqu'une modification
   * de la base de données a été notifiée.
   *
   * Vérification du contenu de l'événement,
   * puis appel de la fonction appropriée.
   */
  function handleDBEvent(event: CustomEvent<DBEventData<Maree>>) {
    // Événement générique
    if (!event.detail) {
      fetchAll();
      return;
    }

    const { type, id: annee, data } = event.detail;

    // En fonction d'un événement particulier
    switch (type) {
      case "create":
        fetchAll();
        break;

      case "delete":
        _delete(annee as number);
        break;

      default:
        fetchAll();
        break;
    }
  }

  /**
   * Supprimer les marées d'une année.
   *
   * @param annee Années des marées
   */
  function _delete(annee: number) {
    update((lignes) => {
      if (lignes) {
        lignes = lignes.filter(
          ({ date }) => date.slice(0, 4) !== annee.toString()
        );
      }

      return lignes;
    });

    mareesAnnees.update((annees) => {
      annees = annees.filter((_annee) => _annee !== annee.toString());

      return annees;
    });
  }
}

export const mareesAnnees = writable<string[]>(null, (set) => {
  fetcher<string[]>("marees/annees").then((annees) =>
    set(annees.sort((a, b) => Number(b) - Number(a)))
  );
});
