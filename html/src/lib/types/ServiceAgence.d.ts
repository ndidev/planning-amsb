/**
 * Service de l'agence.
 */
declare type ServiceAgence = {
  /**
   * Identifiant du service.
   */
  service: string;

  /**
   * Nom pour l'affichage du service à l'utilisateur.
   */
  affichage: string;

  /**
   * Intitulé de l'entreprise lorsque le service est utilisé.
   */
  nom: string;

  /**
   * 1re ligne de l'adressse du service.
   */
  adresse_ligne_1: string;

  /**
   * 2e ligne de l'adressse du service.
   */
  adresse_ligne_2: string;

  /**
   * Code postal du service.
   */
  cp: string;

  /**
   * Ville du service.
   */
  ville: string;

  /**
   * Pays du service.
   */
  pays: string;

  /**
   * Numéro(s) de téléphone fixe(s) du service.
   */
  telephone: string;

  /**
   * Numéro(s) de téléphone mobile(s) du service.
   */
  mobile: string;

  /**
   * Adresse e-mail du service.
   */
  email: string;
};
