import type { Tiers } from "@app/types";

/**
 * Ligne de bandeau d'information.
 */
export type ConfigBandeauInfo = {
  /**
   * Identifiant de la ligne.
   */
  id: number;

  /**
   * Rubrique de la ligne.
   */
  module: "bois" | "vrac" | "consignation" | "chartering";

  /**
   * La ligne est affichée sur PC.
   */
  pc: boolean;

  /**
   * La ligne est affichée sur TV.
   */
  tv: boolean;

  /**
   * Code couleur (#rrggb) de la ligne.
   */
  couleur: string;

  /**
   * Texte de la ligne.
   */
  message: string;
};

/**
 * Configuration PDF.
 */
export type ConfigPDF = {
  /**
   * Identifiant de la configuration.
   */
  id: number;

  /**
   * Rubrique du PDF.
   */
  module: "bois" | "vrac";

  /**
   * Identifiant du fournisseur.
   */
  fournisseur: Tiers["id"];

  /**
   * `true` si l'envoi automatique est activé.
   */
  envoi_auto: boolean;

  /**
   * Liste des adresses e-mail.
   */
  liste_emails: string;

  /**
   * Nombre de jours avant la date du jour pour lesquels récupérer les informations RDV.
   */
  jours_avant: number;

  /**
   * Nombre de jours après la date du jour pour lesquels récupérer les informations RDV.
   */
  jours_apres: number;
};
