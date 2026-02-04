import { fetcher } from "@app/utils/src/fetcher";
import { UserRoles } from "./UserRoles";
import { AccountStatus } from "./AccountStatus";
import type { Roles, ModuleId, CompteUtilisateur } from "@app/types";

/**
 * Classe utilitaire permettant de vérifier les permissions d'un utilisateur.
 */
export class User {
  /**
   * Identifiant unique de l'utilisateur.
   */
  uid: CompteUtilisateur["uid"];

  /**
   * Login de l'utilisateur.
   */
  login: CompteUtilisateur["login"];

  /**
   * Nom de l'utilisateur.
   */
  nom: CompteUtilisateur["nom"];

  /**
   * Rôles de l'utilisateur.
   */
  #roles: Roles;

  /**
   * Statut du compte.
   */
  statut: AccountStatus;

  constructor(
    user: UserInfo = {
      uid: "",
      login: "",
      nom: "",
      roles: {},
      statut: AccountStatus.INACTIVE,
    },
  ) {
    this.uid = user.uid;
    this.login = user.login;
    this.nom = user.nom;
    this.#roles = user.roles;
    this.statut = user.statut;
  }

  /**
   * Retourne `true` si l'utilisateur est connecté et le compte est activé.
   */
  get canUseApp() {
    return this.login !== "" && this.statut === AccountStatus.ACTIVE;
  }

  /**
   * Vérifie si l'utilisateur peut accéder à une rubrique.
   *
   * @param rubrique Rubrique à laquelle accéder
   */
  canAccess(rubrique: ModuleId) {
    return (this.#roles[rubrique] ?? -1) >= UserRoles.ACCESS;
  }

  /**
   * Vérifie si l'utilisateur peut modifier une rubrique.
   *
   * @param rubrique Rubrique à modifier
   */
  canEdit(rubrique: ModuleId) {
    return (this.#roles[rubrique] ?? -1) >= UserRoles.EDIT;
  }

  /**
   * Retourne le niveau d'accès d'un utilisateur à une rubrique.
   *
   * @param rubrique Nom (code) de la rubrique
   * @returns Niveau d'accès de la rubrique ou -1 si la rubrique n'existe pas
   */
  getRole(rubrique: ModuleId): -1 | 0 | 1 | 2 {
    return this.#roles[rubrique] ?? -1;
  }

  /**
   * Vérifie si l'utilisateur est administrateur.
   */
  get isAdmin() {
    return (this.#roles.admin ?? -1) >= UserRoles.ACCESS;
  }

  /**
   * Déconnecter l'utilisateur courant.
   *
   * Envoie une requête à l'API pour _logout_ et supprime `localStorage`.
   */
  async logout() {
    await fetcher("logout", { prefix: "auth" });

    localStorage.clear();
  }
}

export type UserInfo = {
  /**
   * Identifiant unique de l'utilisateur.
   */
  uid: CompteUtilisateur["uid"];

  /**
   * Login de l'utilisateur.
   */
  login: CompteUtilisateur["login"];

  /**
   * Nom de l'utilisateur.
   */
  nom: CompteUtilisateur["nom"];

  /**
   * Rôles de l'utilisateur.
   */
  roles: Roles;

  /**
   * Statut du compte.
   */
  statut: AccountStatus;
};
