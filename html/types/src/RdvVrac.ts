import type { Tiers, ProduitVrac, QualiteVrac } from "@app/types";

/**
 * RDV de planning vrac.
 */
export type RdvVrac = {
  /**
   * Identifiant du RDV.
   */
  id: number | null;

  /**
   * Date du RDV.
   * "yyyy-mm-dd"
   */
  date_rdv: string;

  /**
   * Heure du RDV.
   * "HH:mm"
   */
  heure: string | null;

  /**
   * Code produit.
   */
  produit: ProduitVrac["id"];

  /**
   * Code qualité.
   */
  qualite: QualiteVrac["id"] | null;

  /**
   * Quantité.
   */
  quantite: number;

  /**
   * `true` si la quantité indiquée ne doit pas être dépassée.
   */
  max: boolean;

  /**
   * `true` si la commande a été préparée.
   */
  commande_prete: boolean;

  /**
   * Code du tiers fournisseur.
   */
  fournisseur: Tiers["id"];

  /**
   * Code du tiers client.
   */
  client: Tiers["id"];

  /**
   * Code du tiers transporteur.
   */
  transporteur: Tiers["id"] | null;

  /**
   * Numéro de commande.
   */
  num_commande: string;

  /**
   * Commentaire.
   */
  commentaire: string;
};
