/**
 * Marée.
 */
export type Maree = {
  /**
   * Date de la marée.
   * "yyyy-mm-dd"
   */
  date: string;

  /**
   * Heure de la pleine mer.
   * "HH:mm"
   */
  heure: string;

  /**
   * Hauteur d'eau à Cesson (en mètres).
   */
  te_cesson: number;

  /**
   * Hauteur d'eau au bassin (en mètres).
   */
  te_bassin: number;
};
