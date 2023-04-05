/**
 * Produit vrac.
 */
export type ProduitVrac = {
  /**
   * Identifiant du produit.
   */
  id: number;

  /**
   * Nom du produit.
   */
  nom: string;

  /**
   * Couleur d'affichage du produit.
   */
  couleur: string;

  /**
   * Unité du produit.
   */
  unite: string;

  /**
   * Qualités du produit.
   */
  qualites: QualiteVrac[];
};

/**
 * Qualité d'un produit vrac.
 */
export type QualiteVrac = {
  /**
   * Identifiant de la qualité.
   */
  id: number;

  /**
   * Identifiant du produit auquel est rattaché la qualité.
   */
  produit: number;

  /**
   * Nom de la qualité.
   */
  nom: string;

  /**
   * Couleur d'affichage de la qualité.
   */
  couleur: string;
};
