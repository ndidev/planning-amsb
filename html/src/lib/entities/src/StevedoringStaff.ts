import { Entity } from "../";

/**
 * Personnel de manutention.
 */
export default class StevedoringStaff extends Entity {
  /**
   * Identifiant du personnel.
   */
  id: number = null;

  /**
   * Prénom.
   */
  firstname: string = "";

  /**
   * Nom de famille.
   */
  lastname: string = "";

  /**
   * Nom complet.
   */
  fullname: string = "";

  /**
   * Téléphone.
   */
  phone: string = "";

  /**
   * Type de contrat.
   */
  type: "mensuel" | "interim" = "mensuel";

  /**
   * Agency d'intérim.
   */
  tempWorkAgency: string | null = null;

  /**
   * Actif.
   */
  isActive: boolean = true;

  /**
   * Commentaires.
   */
  comments: string = "";

  /**
   * Date de suppression.
   */
  deletedAt: Date | null = null;
}
