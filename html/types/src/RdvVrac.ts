import type {
  Tiers,
  ProduitVrac,
  QualiteVrac,
  StevedoringStaff,
} from "@app/types";

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
