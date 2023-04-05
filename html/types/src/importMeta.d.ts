/// <reference types="vite/client" />

interface ImportMetaEnv {
  /**
   * Longueur minimum autorisée pour le mot de passe.
   */
  VITE_LONGUEUR_MINI_PASSWORD: string;

  /**
   * Nombre de tentatives de connexions maximal
   * avant que le compte ne soit bloqué.
   */
  VITE_MAX_LOGIN_ATTEMPTS: string;

  /**
   * Adresse du serveur de l'API pour le développement local.
   */
  VITE_API_HOST?: string;
}
