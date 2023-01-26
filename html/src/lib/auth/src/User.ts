import { env, sitemap } from "@app/utils";
import { UserRoles, AccountStatus } from "@app/auth";

/**
 * Classe utilitaire permettant de vérifier les permissions d'un utilisateur.
 */
export class User {
  /**
   * Login de l'utilisateur.
   */
  login: string;

  /**
   * Nom de l'utilisateur.
   */
  nom: string;

  /**
   * Rôles de l'utilisateur.
   */
  #roles: Roles;

  /**
   * Statut du compte.
   */
  statut: AccountStatus;

  constructor(
    user: {
      login: string;
      nom: string;
      roles: Roles;
      statut: AccountStatus;
    } = {
      login: "",
      nom: "",
      roles: {},
      statut: AccountStatus.INACTIVE,
    }
  ) {
    this.login = user.login;
    this.nom = user.nom;
    this.#roles = user.roles;
    this.statut = user.statut;

    // En cas de manque d'un module dans les caractéristiques de l'utilisateur
    // le mettre à zéro par défaut
    for (const module of sitemap.keys()) {
      if (this.#roles[module] === undefined) {
        this.#roles[module] = UserRoles.NONE;
      }
    }
  }

  /**
   * Vérifie si l'utilisateur peut accéder à une rubrique.
   *
   * @param rubrique Rubrique à laquelle accéder
   */
  canAccess(rubrique: string): boolean {
    return (this.#roles[rubrique] || -1) >= UserRoles.ACCESS;
  }

  /**
   * Vérifie si l'utilisateur peut modifier une rubrique.
   *
   * @param rubrique Rubrique à modifier
   */
  canEdit(rubrique: string): boolean {
    return (this.#roles[rubrique] || -1) >= UserRoles.EDIT;
  }

  /**
   * Retourne le niveau d'accès d'un utilisateur à une rubrique.
   *
   * @param rubrique Nom (code) de la rubrique
   * @returns Niveau d'accès de la rubrique ou -1 si la rubrique n'existe pas
   */
  getRole(rubrique: string): -1 | 0 | 1 | 2 {
    return this.#roles[rubrique] || -1;
  }

  /**
   * Vérifie si l'utilisateur est administrateur.
   */
  get isAdmin(): boolean {
    return (this.#roles.admin || -1) >= UserRoles.ACCESS;
  }

  /**
   * Déconnecter l'utilisateur courant.
   *
   * Envoie une requête à l'API pour _logout_ et supprime `localStorage`.
   */
  async logout() {
    const url = new URL(env.auth);
    url.pathname += "logout";

    const reponse = await fetch(url);

    if (!reponse.ok) {
      throw new Error(`${reponse.status}, ${reponse.statusText}`);
    }

    localStorage.clear();
    location.pathname = env.prefix + "/";
  }
}
