import type { Tiers, StevedoringStaff } from "@app/types";

/**
 * RDV de planning bois.
 */
export type RdvBois = {
  /**
   * Identifiant du RDV.
   */
  id: number | null;

  /**
   * `true` si le RDV est en attente (= pas de date confirmée).
   */
  attente: boolean;

  /**
   * Date du RDV.
   * "yyyy-mm-dd"
   */
  date_rdv: string | null;

  /**
   * Heure d'arrivée du camion.
   * "HH:mm"
   */
  heure_arrivee: string | null;

  /**
   * Heure de départ du camion.
   * "HH:mm"
   */
  heure_depart: string | null;

  /**
   * Code du tiers du lieu de chargement.
   */
  chargement: Tiers["id"];

  /**
   * Code du tiers client.
   */
  client: Tiers["id"];

  /**
   * Code du tiers du lieu de livraison.
   */
  livraison: Tiers["id"] | null;

  /**
   * Code du tiers transporteur.
   */
  transporteur: Tiers["id"] | null;

  /**
   * Code du tiers affréteur.
   */
  affreteur: Tiers["id"] | null;

  /**
   * Code du tiers fournisseur.
   */
  fournisseur: Tiers["id"];

  /**
   * Commande prête.
   */
  commande_prete?: boolean;

  /**
   * `true` si la confirmation d'affrètement a été envoyée au transporteur.
   */
  confirmation_affretement: boolean;

  /**
   * Numéro de bon de livraison.
   */
  numero_bl: string;

  /**
   * Commentaire public.
   */
  commentaire_public: string;

  /**
   * Commentaire caché.
   */
  commentaire_cache: string;

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
 * Nombre de camions de bois pour chaque date.
 */
export type CamionsParDate = {
  total: number;
  attendus?: number;
  sur_parc?: number;
  charges?: number;
};

export type TimberFilter = {
  date_debut?: string;
  date_fin?: string;
  fournisseur?: RdvBois["fournisseur"][];
  client?: RdvBois["client"][];
  chargement?: RdvBois["chargement"][];
  livraison?: RdvBois["livraison"][];
  transporteur?: RdvBois["transporteur"][];
  affreteur?: RdvBois["affreteur"][];
};
