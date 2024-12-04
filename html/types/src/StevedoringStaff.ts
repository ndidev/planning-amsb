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
   * Téléphone.
   */
  phone: string;

  /**
   * Type de contrat.
   */
  type: "cdi" | "interim";

  /**
   * Agency d'intérim.
   */
  tempWorkAgency: string | null;

  /**
   * Actif.
   */
  active: boolean;

  /**
   * Commentaires.
   */
  comments: string;

  /**
   * Date de suppression.
   */
  deletedAt: Date | null;
};
