import type { Tiers, StevedoringStaff } from "@app/types";

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
   * Commentaire public.
   */
  commentaire_public: string;

  /**
   * Commentaire privé.
   */
  commentaire_prive: string;

  /**
   * `true` si le RDV est à afficher sur l'écran TV.
   */
  showOnTv: boolean;

  /**
   * `true` si le RDV est archivé.
   */
  archive: boolean;

  /**
   * Personnel de manutention ayant effecté les opérations.
   */
  dispatch: {
    staffId: StevedoringStaff["id"];
    date: string;
    remarks: string;
    new?: boolean;
  }[];
};

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

export type BulkPlanningFilter = Partial<{
  date_debut: string;
  date_fin: string;
  produit: RdvVrac["produit"][];
  qualite: RdvVrac["qualite"][];
  fournisseur: RdvVrac["fournisseur"][];
  client: RdvVrac["client"][];
  transporteur: RdvVrac["transporteur"][];
  archives: boolean;
  tv: boolean;
}>;

export type BulkDispatchFilter = Partial<{
  startDate: string;
  endDate: string;
  staff: StevedoringStaff["id"][];
}>;
