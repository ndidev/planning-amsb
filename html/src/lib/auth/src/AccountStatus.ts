/**
 * Statut des comptes utilisateurs.
 */
export enum AccountStatus {
  /**
   * Compte actif.
   *
   * Le compte peut être utilisé normalement.
   */
  ACTIVE = "active",

  /**
   * Compte en attente d'activation.
   *
   * Le compte est créé mais le mot de passe
   * n'a pas été initialisé par l'utilisateur.
   */
  PENDING = "pending",

  /**
   * Compte désactivé.
   *
   * Le compte a été désactivé volontairement par un administrateur
   * car l'utilisateur ne l'utilise plus
   * (salarié ayant quitté l'entreprise, par exemple).
   *
   * Seul un administrateur peur le réactiver.
   */
  INACTIVE = "inactive",

  /**
   * Compte bloqué.
   *
   * Le compte est bloqué pour l'une des raisons suivantes :
   *  - nombre de tentatives de connexions dépassé
   *
   * Seul un administrateur peur le débloquer.
   */
  LOCKED = "locked",

  /**
   * Compte supprimé.
   *
   * Le compte a été supprimé.
   *
   * Il est conservé pour l'historique des opérations mais n'est pas récupérable.
   */
  DELETED = "deleted",
}
