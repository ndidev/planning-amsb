import type { Roles } from "./Modules";
import type { AccountStatus } from "@app/auth";

/**
 * Objet compte utilisateur.
 */
export interface CompteUtilisateur {
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
  statut: AccountStatus;

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
