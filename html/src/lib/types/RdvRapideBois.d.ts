/**
 * Rdv rapide bois.
 */
declare type RdvRapideBois = {
  /**
   * Identifiant du RDV rapide.
   */
  id: number;

  /**
   * Module du RDV rapide.
   */
  module: string;

  /**
   * Identifiant du fournisseur.
   */
  fournisseur: number;

  /**
   * Identifiant du transporteur.
   */
  transporteur: number;

  /**
   * Identifiant de l'affréteur.
   */
  affreteur: number;

  /**
   * Identifiant du chargement.
   */
  chargement: number;

  /**
   * Identifiant du client.
   */
  client: number;

  /**
   * Identifiant de la livraison.
   */
  livraison: number;
};
