/**
 * Connexion d'un client.
 */
declare type Connection = {
  /**
   * Identifiant unique de la connexion.
   */
  id: string;

  /**
   * UID de l'utilisateur.
   */
  userId: string;

  /**
   * Requête HTTP.
   */
  request: http.IncomingMessage;

  /**
   * Réponse HTTP.
   */
  response: http.ServerResponse;

  /**
   * Liste des événements souscrits.
   */
  subscriptions: string[];
};
