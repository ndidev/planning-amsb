/**
 * Roles des utilisateurs.
 */
export enum UserRoles {
  /**
   * Aucun.
   *
   * L'utilisateur ne peut pas accéder à la rubrique.
   */
  NONE = 0,

  /**
   * Visualisation.
   *
   * L'utilisateur peut visualiser la rubrique mais ne peut pas modifier.
   * L'utilisateur a accès aux rubriques de type "Accès/Pas accès".
   */
  ACCESS = 1,

  /**
   * Modification.
   *
   * L'utilisateur peut voir et modifier la rubrique.
   */
  EDIT = 2,
}
