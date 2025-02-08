import { writable } from "svelte/store";

import Notiflix from "notiflix";

import { fetcher } from "@app/utils";
import { HTTP } from "@app/errors";
import type {
  DBEventData,
  Collection,
  AjoutRapide,
  AjoutsRapides,
} from "@app/types";

type TypeConfig = AjoutRapide;

type CollectionAjoutsRapides = Collection<TypeConfig>;

const empty: CollectionAjoutsRapides = {
  bois: new Map(),
  // vrac: new Map(),
};

const modelesAjoutsRapides: AjoutsRapides = {
  bois: {
    id: null,
    module: "bois",
    fournisseur: null,
    transporteur: null,
    affreteur: null,
    chargement: null,
    client: null,
    livraison: null,
  },
  // vrac: {
  //   id: null,
  //   module: "vrac",
  //   produit: null,
  //   qualite: null,
  //   fournisseur: null,
  //   client: null,
  //   transporteur: null,
  // },
};

const endpoint = "config/ajouts-rapides";

const initial = structuredClone(empty);

// Config - Ajouts rapides
export const configAjoutsRapides = (function () {
  const { subscribe, set, update } = writable<CollectionAjoutsRapides>(
    initial,
    () => {
      fetchAll();

      document.addEventListener(`planning:${endpoint}`, handleDBEvent);
      document.addEventListener(`planning:sse-reconnect`, fetchAll);

      return () => {
        document.removeEventListener(`planning:${endpoint}`, handleDBEvent);
        document.removeEventListener(`planning:sse-reconnect`, fetchAll);
      };
    }
  );

  return {
    subscribe,
    new: _new,
    cancel: _cancel,
    create: _dbCreate,
    update: _dbUpdate,
    delete: _dbDelete,
    endpoint,
  };

  // FONCTIONS

  /**
   * Récupérer toutes les données.
   */
  async function fetchAll() {
    try {
      // const configs: TypeConfig[] = await fetcher(endpoint);
      const configs: AjoutsRapides = await fetcher(endpoint);

      const updated = structuredClone(empty);

      // for (const config of configs) {
      //   updated[config.module].set(config.id, config);
      // }
      for (const module in configs) {
        if (!(module in updated)) continue;

        for (const config of configs[module]) {
          updated[module].set(config.id, config);
        }
      }

      set(updated);
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
   * Ajouter une config.
   *
   * @param module
   */
  function _new(module: TypeConfig["module"]) {
    update((configs) => {
      const tmpConfig = structuredClone(modelesAjoutsRapides[module]);
      tmpConfig.id = Math.random();
      tmpConfig.module = module;

      configs[module].set(tmpConfig.id, tmpConfig);

      return configs;
    });
  }

  /**
   * Annuler un ajout de nouvelle config.
   *
   * @param id Identifiant de la config
   */
  function _cancel(id: TypeConfig["id"]) {
    _delete(id);
  }

  /**
   * Créer un RDV rapide.
   * (= valider un ajout de nouvelle config)
   *
   * @param config Données du RDV rapide
   */
  async function _dbCreate(config: TypeConfig) {
    const nouvelleConfig: TypeConfig = await fetcher(
      `${endpoint}/${config.module}`,
      {
        requestInit: {
          method: "POST",
          body: JSON.stringify(config),
        },
      }
    );

    update((configs) => {
      // Suppression de la config temporaire
      configs[config.module].delete(config.id);

      // Ajout de la nouvelle config
      configs[config.module].set(nouvelleConfig.id, nouvelleConfig);

      return configs;
    });
  }

  /**
   * Mettre à jour une config.
   *
   * @param config Données de la config
   */
  async function _dbUpdate(config: TypeConfig) {
    await fetcher(`${endpoint}/${config.module}/${config.id}`, {
      requestInit: {
        method: "PUT",
        body: JSON.stringify(config),
      },
    });

    _update(config);
  }

  /**
   * Supprimer une config.
   *
   * @param config Configuration à supprimer
   */
  async function _dbDelete(config: TypeConfig) {
    await fetcher(`${endpoint}/${config.module}/${config.id}`, {
      requestInit: {
        method: "DELETE",
      },
    });

    _delete(config.id);
  }

  /**
   * Gestionnaire d'événement appelé lorsqu'une modification
   * de la base de données a été notifiée.
   *
   * Vérification du contenu de l'événement,
   * puis appel de la fonction appropriée.
   */
  function handleDBEvent(event: CustomEvent<DBEventData<TypeConfig>>) {
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
          _update(data);
          break;

        case "delete":
          _delete(id as number);
          break;

        default:
          fetchAll();
          break;
      }
    } catch (error) {
      fetchAll();
    }
  }

  /**
   * Créer une ressource.
   *
   * @param config Données de la ressource
   */
  function _create(config: TypeConfig) {
    update((configs) => {
      configs[config.module].set(config.id, config);

      return configs;
    });
  }

  /**
   * Mettre à jour une ressource.
   *
   * @param config Données de la ressource
   */
  function _update(config: TypeConfig) {
    update((configs) => {
      if (!configs[config.module].has(config.id)) throw new Error();

      configs[config.module].set(config.id, config);

      return configs;
    });
  }

  /**
   * Supprimer une ressource.
   *
   * @param id ID de la ressource.
   */
  function _delete(id: TypeConfig["id"]) {
    update((configs) => {
      for (const module in configs) {
        configs[module].delete(id);
      }

      return configs;
    });
  }
})();
