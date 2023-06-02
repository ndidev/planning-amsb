import { writable } from "svelte/store";

import Notiflix from "notiflix";

import { fetcher } from "@app/utils";
import { AccountStatus } from "@app/auth";
import { HTTP } from "@app/errors";
import type { CompteUtilisateur, DBEventData } from "@app/types";

const endpoint = "admin/users";

const modeleCompte: CompteUtilisateur = {
  uid: null,
  login: "",
  nom: "",
  statut: AccountStatus.PENDING,
  roles: {
    admin: 0,
    bois: 0,
    chartering: 0,
    config: 0,
    consignation: 0,
    tiers: 0,
    vrac: 0,
  },
  commentaire: "",
  last_connection: "",
  historique: "",
  self: false,
};

type MapUtilisateurs = Map<CompteUtilisateur["uid"], CompteUtilisateur>;

const initial = new Map();

// Admin - Comptes
export const adminUsers = (function () {
  const { subscribe, set, update } = writable<MapUtilisateurs>(initial, () => {
    fetchAll();

    document.addEventListener(`planning:${endpoint}`, handleDBEvent);

    return () =>
      document.removeEventListener(`planning:${endpoint}`, handleDBEvent);
  });

  return {
    subscribe,
    new: _new,
    cancel: _cancel,
    create: _dbCreate,
    update: _dbUpdate,
    deactivate: _dbDelete,
    reset: _dbReset,
    endpoint,
  };

  // FONCTIONS

  /**
   * Récupérer toutes les données.
   */
  async function fetchAll() {
    try {
      const comptes: CompteUtilisateur[] = await fetcher(endpoint);

      const updated = new Map(comptes.map((compte) => [compte.uid, compte]));

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
   * Ajouter un nouveau compte.
   *
   * @param module
   */
  function _new() {
    update((comptes) => {
      const tmpCompte = structuredClone(modeleCompte);
      tmpCompte.uid = `new_${Math.random()}`;

      return new Map([[tmpCompte.uid, tmpCompte], ...comptes]);
    });
  }

  /**
   * Annuler un ajout de nouveau compte.
   *
   * @param id Identifiant du compte
   */
  function _cancel(id: CompteUtilisateur["uid"]) {
    update((comptes) => (comptes.delete(id), comptes));
  }

  /**
   * Créer un compte.
   * (= valider un ajout de nouveau compte)
   *
   * @param data Données du compte
   */
  async function _dbCreate(data: CompteUtilisateur) {
    const nouveauCompte: CompteUtilisateur = await fetcher(endpoint, {
      requestInit: {
        method: "POST",
        body: JSON.stringify(data),
      },
    });

    update((comptes) => {
      // Suppression du compte temporaire
      comptes.delete(data.uid);

      const arrComptes = [...comptes.values()];
      arrComptes.push(nouveauCompte);
      arrComptes.sort((a, b) => (a.login < b.login ? -1 : 1));

      return new Map(arrComptes.map((compte) => [compte.uid, compte]));
    });
  }

  /**
   * Mettre à jour un compte.
   *
   * @param data Données du compte
   */
  async function _dbUpdate(data: CompteUtilisateur) {
    await fetcher(`${endpoint}/${data.uid}`, {
      requestInit: {
        method: "PUT",
        body: JSON.stringify(data),
      },
    });

    _update(data);
  }

  /**
   * Désactiver un compte.
   *
   * @param id Identifiant du compte
   */
  async function _dbDelete(id: CompteUtilisateur["uid"]) {
    await fetcher(`${endpoint}/${id}`, {
      requestInit: {
        method: "DELETE",
      },
    });

    _delete(id);
  }

  /**
   * Réinitialiser un compte.
   *
   * @param id Identifiant du compte
   */
  async function _dbReset(id: CompteUtilisateur["uid"]) {
    await fetcher(`${endpoint}/${id}/reset`, {
      requestInit: {
        method: "PUT",
      },
    });

    update((comptes) => {
      if (!comptes.has(id)) throw new Error();

      const compte = comptes.get(id);
      compte.statut = AccountStatus.PENDING;
      comptes.set(id, compte);

      return comptes;
    });
  }

  /**
   * Gestionnaire d'événement appelé lorsqu'une modification
   * de la base de données a été notifiée.
   *
   * Vérification du contenu de l'événement,
   * puis appel de la fonction appropriée.
   */
  function handleDBEvent(event: CustomEvent<DBEventData<CompteUtilisateur>>) {
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
          if (data) {
            _update(data);
          } else {
            fetchAll();
          }
          break;

        case "delete":
          _delete(id as CompteUtilisateur["uid"]);
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
   * Créer une ressource.
   *
   * @param compte Données de la ressource
   */
  function _create(compte: CompteUtilisateur) {
    update((comptes) => {
      comptes.set(compte.uid, compte);

      return comptes;
    });
  }

  /**
   * Mettre à jour une ressource.
   *
   * @param compte Données de la ressource
   */
  function _update(compte: CompteUtilisateur) {
    update((comptes) => {
      if (!comptes.has(compte.uid)) throw new Error();

      comptes.set(compte.uid, compte);

      return comptes;
    });
  }

  /**
   * Supprimer une ressource.
   *
   * @param id ID de la ressource.
   */
  function _delete(id: CompteUtilisateur["uid"]) {
    update((comptes) => {
      if (!comptes.has(id)) throw new Error();

      const compte = comptes.get(id);
      compte.statut = AccountStatus.INACTIVE;
      comptes.set(id, compte);

      return comptes;
    });
  }
})();
