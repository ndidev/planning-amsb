/**
 * Ligne de bandeau d'information.
 */
export type ConfigBandeauInfo = {
  /**
   * Identifiant de la ligne.
   */
  id: number;

  /**
   * Rubrique de la ligne.
   */
  module: "bois" | "vrac" | "consignation" | "chartering";

  /**
   * La ligne est affichée sur PC.
   */
  pc: boolean;

  /**
   * La ligne est affichée sur TV.
   */
  tv: boolean;

  /**
   * Code couleur (#rrggb) de la ligne.
   */
  couleur: string;

  /**
   * Texte de la ligne.
   */
  message: string;
};
