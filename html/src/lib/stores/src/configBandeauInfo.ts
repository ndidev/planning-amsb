import { writable } from "svelte/store";

import Notiflix from "notiflix";

import { fetcher } from "@app/utils";
import { HTTP } from "@app/errors";
import type { DBEventData, Collection, ConfigBandeauInfo } from "@app/types";

type TypeConfig = ConfigBandeauInfo;

type CollectionConfigBandeauInfo = Collection<TypeConfig>;

const empty: CollectionConfigBandeauInfo = {
  bois: new Map(),
  vrac: new Map(),
  consignation: new Map(),
  chartering: new Map(),
};

const modeleConfigInfo: TypeConfig = {
  id: null,
  module: null,
  pc: true,
  tv: false,
  couleur: "#000000",
  message: "",
};

const endpoint = "config/bandeau-info";

const initial = structuredClone(empty);

// Config - BandeauInfo
export const configBandeauInfo = (function () {
  const { subscribe, set, update } = writable<CollectionConfigBandeauInfo>(
    initial,
    () => {
      fetchAll();

      document.addEventListener(`planning:${endpoint}`, handleDBEvent);

      return () => {
        document.removeEventListener(`planning:${endpoint}`, handleDBEvent);
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
      const configs: TypeConfig[] = await fetcher(endpoint);

      const updated = structuredClone(empty);

      for (const config of configs) {
        updated[config.module].set(config.id, config);
      }

      set(updated);
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
   * Ajouter une nouvelle config.
   *
   * @param module
   */
  function _new(module: TypeConfig["module"]) {
    update((configs) => {
      const tmpConfig = structuredClone(modeleConfigInfo);
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
   * Créer une config.
   * (= valider un ajout de nouvelle config)
   *
   * @param data Données de la config
   */
  async function _dbCreate(data: TypeConfig) {
    const nouvelleConfig: TypeConfig = await fetcher(endpoint, {
      requestInit: {
        method: "POST",
        body: JSON.stringify(data),
      },
    });

    update((configs) => {
      // Suppression de la config temporaire
      configs[data.module].delete(data.id);

      // Ajout de la nouvelle config
      configs[data.module].set(nouvelleConfig.id, nouvelleConfig);

      return configs;
    });
  }

  /**
   * Mettre à jour une config.
   *
   * @param data Données de la config
   */
  async function _dbUpdate(data: TypeConfig) {
    await fetcher(`${endpoint}/${data.id}`, {
      requestInit: {
        method: "PUT",
        body: JSON.stringify(data),
      },
    });

    _update(data);
  }

  /**
   * Supprimer une config.
   *
   * @param id Identifiant de la config
   */
  async function _dbDelete(id: TypeConfig["id"]) {
    await fetcher(`${endpoint}/${id}`, {
      requestInit: {
        method: "DELETE",
      },
    });

    _delete(id);
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
