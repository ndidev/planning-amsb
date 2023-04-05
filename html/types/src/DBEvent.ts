/**
 * Données d'une notification de modification de la base de données
 * par le serveur PHP.
 */
export type DBEventData<T> = {
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
  data?: T;
};
