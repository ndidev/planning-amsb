import type { Port, Tiers } from "@app/types";

/**
 * Affrètement maritime.
 */
export type Charter = {
  /**
   * Identifiant de l'affrètement.
   */
  id: number | null;

  /**
   * Statut.
   */
  statut: 0 | 1 | 2 | 3 | 4;

  /**
   * Date de début du laycan.
   */
  lc_debut: string | null;

  /**
   * Date de fin du laycan.
   */
  lc_fin: string | null;

  /**
   * Date de signature de la C/P.
   */
  cp_date: string | null;

  /**
   * Nom du navire.
   */
  navire: string;

  /**
   * Code du tiers de l'affréteur.
   */
  affreteur: Tiers["id"] | null;

  /**
   * Code du tiers de l'armateur.
   */
  armateur: Tiers["id"] | null;

  /**
   * Code du tiers du courtier.
   */
  courtier: Tiers["id"] | null;

  /**
   * Montant d'achat du fret.
   */
  fret_achat: number | null;

  /**
   * Montant de vente du fret.
   */
  fret_vente: number | null;

  /**
   * Montant d'achat des surestaries.
   */
  surestaries_achat: number | null;

  /**
   * Montant de vente des surestaries.
   */
  surestaries_vente: number | null;

  /**
   * Étapes du voyage.
   */
  legs: CharterLeg[];

  /**
   * Commentaire.
   */
  commentaire: string;

  /**
   * `true` si l'affrètement a été archivé.
   */
  archive: boolean;
};

/**
 * Trajet d'un affrètement.
 */
export type CharterLeg = {
  /**
   * Identifiant du trajet.
   */
  id: number;

  /**
   * Identifiant de l'affrètement auquel appartient le trajet.
   */
  charter: Charter["id"];

  /**
   * Date d'établissement du B/L.
   */
  bl_date: string;

  /**
   * Code du port de chargement.
   */
  pol: Port["locode"];

  /**
   * Code du port de déchargement.
   */
  pod: Port["locode"];

  /**
   * Description de la marchandise.
   */
  marchandise: string;

  /**
   * Quantité de marchandise.
   */
  quantite: string;

  /**
   * Commentaire.
   */
  commentaire: string;
};
