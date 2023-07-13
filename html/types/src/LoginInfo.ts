export type LoginInfo = {
  /**
   * Nombre de tentatives maximum de connexion avant le blocage du compte utilisateur.
   */
  MAX_LOGIN_ATTEMPTS: number;

  /**
   * Longueur minimum autorisée du mot de passe utilisateur.
   */
  LONGUEUR_MINI_PASSWORD: number;
};
