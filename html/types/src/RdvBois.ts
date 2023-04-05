import type { Tiers } from "@app/types";

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
};
