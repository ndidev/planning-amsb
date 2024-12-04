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
