import type http from "node:http";

/**
 * Connexion d'un client.
 */
export type Connection = {
  /**
   * Identifiant unique de la connexion.
   */
  id: string;

  /**
   * UID de l'utilisateur.
   */
  userId: string;

  /**
   * Identifiant de la session utilisateur.
   */
  sessionId: string | undefined;

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
