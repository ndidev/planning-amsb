/**
 * Configuration PDF.
 */
declare type ConfigPDF = {
  /**
   * Identifiant de la configuration.
   */
  id: number | string;

  /**
   * Rubrique du PDF.
   */
  module: string;

  /**
   * Identifiant du fournisseur.
   */
  fournisseur: number;

  /**
   * Nom du fournisseur.
   */
  fournisseur_nom: string;

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
