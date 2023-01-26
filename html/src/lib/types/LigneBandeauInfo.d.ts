/**
 * Ligne de bandeau d'information.
 */
declare type LigneBandeauInfo = {
  /**
   * Identifiant de la ligne.
   */
  id: number | string;

  /**
   * Rubrique de la ligne.
   */
  module: string;

  /**
   * La ligne est affichée sur PC.
   */
  pc: 0 | 1;

  /**
   * La ligne est affichée sur TV.
   */
  tv: 0 | 1;

  /**
   * Code couleur (#rrggb) de la ligne.
   */
  couleur: string;

  /**
   * Texte de la ligne.
   */
  message: string;
};
