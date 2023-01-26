/**
 * Objet compte utilisateur.
 */
declare interface CompteUtilisateur {
  /**
   * Identifiant unique de l'utilisateur.
   */
  uid?: string;

  /**
   * Nom de l'utilisateur.
   */
  nom: string;

  /**
   * Login de l'utilisateur.
   */
  login: string;

  /**
   * Statut du compte de l'utilisateur.
   */
  statut: string;

  /**
   * Roles attribués à l'utilisateur.
   */
  roles: Roles;

  /**
   * Commentaire sur le compte.
   */
  commentaire: string;

  /**
   * Date et heure de la dernière connexion.
   */
  last_connection: string;

  /**
   * Historique du compte.
   */
  historique: string;

  /**
   * Le compte est celui de l'utilisateur courant.
   */
  self: boolean;
}
