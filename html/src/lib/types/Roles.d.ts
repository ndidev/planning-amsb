/**
 * Autorisations attribuées à l'utilisateur pour chaque rubrique de l'application.
 */
declare type Roles = {
  /**
   * Accès au planning bois.
   */
  bois?: 0 | 1 | 2;

  /**
   * Accès au planning vrac.
   */
  vrac?: 0 | 1 | 2;

  /**
   * Accès au planning consignation.
   */
  consignation?: 0 | 1 | 2;

  /**
   * Accès au planning affrètement maritime.
   */
  chartering?: 0 | 1 | 2;

  /**
   * Accès à la page des tiers.
   */
  tiers?: 0 | 1;

  /**
   * Accès à la page de configuration.
   */
  config?: 0 | 1;

  /**
   * Accès à l'interface d'administration.
   */
  admin?: 0 | 1;
};
