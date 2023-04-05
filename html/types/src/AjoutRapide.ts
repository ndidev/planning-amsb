import type {
  Tiers,
  RdvBois,
  RdvVrac,
  ProduitVrac,
  QualiteVrac,
} from "@app/types";

export type AjoutsRapides = {
  bois: AjoutRapideBois;
  // vrac: AjoutRapideVrac;
};

/**
 * RDV rapide.
 */
export type AjoutRapide = AjoutsRapides[keyof AjoutsRapides];

/**
 * RDV rapide bois.
 */
export type AjoutRapideBois = {
  /**
   * Identifiant du RDV rapide.
   */
  id: RdvBois["id"];

  /**
   * Module du RDV rapide.
   */
  module: "bois";

  /**
   * Identifiant du fournisseur.
   */
  fournisseur: Tiers["id"];

  /**
   * Identifiant du transporteur.
   */
  transporteur: Tiers["id"];

  /**
   * Identifiant de l'affréteur.
   */
  affreteur: Tiers["id"];

  /**
   * Identifiant du chargement.
   */
  chargement: Tiers["id"];

  /**
   * Identifiant du client.
   */
  client: Tiers["id"];

  /**
   * Identifiant de la livraison.
   */
  livraison: Tiers["id"];
};

export type AjoutRapideVrac = {
  /**
   * Identifiant du RDV rapide.
   */
  id: RdvVrac["id"];

  /**
   * Module du RDV rapide.
   */
  module: "vrac";

  /**
   * Identifiant du produit.
   */
  produit: ProduitVrac["id"];

  /**
   * Identifiant de la qualité.
   */
  qualite: QualiteVrac["id"];

  /**
   * Identifiant du fournisseur.
   */
  fournisseur: Tiers["id"];

  /**
   * Identifiant du client.
   */
  client: Tiers["id"];

  /**
   * Identifiant du transporteur.
   */
  transporteur?: Tiers["id"];
};
