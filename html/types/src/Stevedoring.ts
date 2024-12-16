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

/**
 * Personnel de manutention.
 */
export type StevedoringEquipment = {
  /**
   * Identifiant du personnel.
   */
  id: number | null;

  /**
   * Type.
   */
  type: string;

  /**
   * Marque.
   */
  brand: string;

  /**
   * Modèle.
   */
  model: string;

  /**
   * Numéro interne.
   */
  internalNumber: string;

  /**
   * Numéro de série.
   */
  serialNumber: string;

  /**
   * Commentaires.
   */
  comments: string;

  /**
   * Actif.
   */
  isActive: boolean;
};

export type TempWorkHours = {
  /**
   * Identifiant de l'enregistrement.
   */
  id: number | null;

  /**
   * Identifiant de l'employé intérimaire.
   */
  staffId: number;

  /**
   * Date.
   */
  date: string;

  /**
   * Nombre d'heures travaillées.
   */
  hoursWorked: number;

  /**
   * Commentaires.
   */
  comments: string;
};
