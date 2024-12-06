/**
 * Personnel de manutention.
 */
export type StevedoringStaff = {
  /**
   * Identifiant du personnel.
   */
  id: number | null;

  /**
   * Prénom.
   */
  firstname: string;

  /**
   * Nom de famille.
   */
  lastname: string;

  /**
   * Nom complet.
   */
  fullname: string;

  /**
   * Téléphone.
   */
  phone: string;

  /**
   * Type de contrat.
   */
  type: "mensuel" | "interim";

  /**
   * Agency d'intérim.
   */
  tempWorkAgency: string | null;

  /**
   * Actif.
   */
  isActive: boolean;

  /**
   * Commentaires.
   */
  comments: string;

  /**
   * Date de suppression.
   */
  deletedAt: Date | null;
};
