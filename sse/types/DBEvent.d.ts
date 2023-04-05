/**
 * Données d'une notification de modification de la base de données
 * par le serveur PHP.
 */
declare type DBEventData = {
  /**
   * Nom de l'événement.
   */
  name: string;

  /**
   * Type de l'événement (update, create, etc.).
   */
  type: string;

  /**
   * Id de la ressource modifiée.
   */
  id: number | string;

  /**
   * Données de la ressource modifiée.
   */
  data?: any;

  /**
   * Identifiant unique de la connexion SSE associée à la fenêtre du client.
   */
  origin?: string | null;
};
