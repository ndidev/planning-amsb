/**
 * Escale du planning consignation.
 */
export type EscaleConsignation = {
  /**
   * Identifiant de l'escale.
   */
  id: number | null;

  /**
   * Nom du navire.
   */
  navire: string;

  /**
   * Numéro de voyage.
   */
  voyage: string | null;

  /**
   * Code du tiers armateur.
   */
  armateur: number | null;

  /**
   * Date de l'ETA.
   */
  eta_date: string | null;

  /**
   * Heure de l'ETA.
   */
  eta_heure: string;

  /**
   * Date de la NOR.
   */
  nor_date: string | null;

  /**
   * Heure de la NOR.
   */
  nor_heure: string;

  /**
   * Date POB.
   */
  pob_date: string | null;

  /**
   * Heure POB.
   */
  pob_heure: string;

  /**
   * Date de l'ETB.
   */
  etb_date: string | null;

  /**
   * Heure de l'ETB.
   */
  etb_heure: string;

  /**
   * Date de début des opérations.
   */
  ops_date: string | null;

  /**
   * Heure de début des opérations.
   */
  ops_heure: string;

  /**
   * Date de l'ETC.
   */
  etc_date: string | null;

  /**
   * Heure de l'ETC.
   */
  etc_heure: string;

  /**
   * Date de l'ETD.
   */
  etd_date: string | null;

  /**
   * Heure de l'ETD.
   */
  etd_heure: string;

  /**
   * Tireant d'eau d'arrivée.
   */
  te_arrivee: number | null;

  /**
   * Tireant d'eau de départ.
   */
  te_depart: number | null;

  /**
   * UN Locode du dernier port touché.
   */
  last_port: string;

  /**
   * UN Locode du prochain port touché.
   */
  next_port: string;

  /**
   * Port d'escale.
   */
  call_port: string;

  /**
   * Quai d'escale.
   */
  quai: string;

  /**
   * Liste des marchandises.
   */
  marchandises: {
    id: number;
    escale_id: number;
    operation: "Import" | "Export";
    marchandise: string;
    client: string;
    environ: boolean;
    tonnage_bl: number;
    cubage_bl: number;
    nombre_bl: number;
    tonnage_outturn: number;
    cubage_outturn: number;
    nombre_outturn: number;
  }[];

  /**
   * Commentaire.
   */
  commentaire: string;
};
