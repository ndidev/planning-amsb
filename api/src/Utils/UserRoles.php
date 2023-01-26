<?php

namespace Api\Utils;

/**
 * Statuts des comptes utilisateurs.
 */
enum UserRoles: int
{
  /**
   * Aucun.
   *
   * L'utilisateur ne peut pas accéder à la rubrique.
   */
  case NONE = 0;

  /**
   * Visualisation.
   *
   * L'utilisateur peut visualiser la rubrique mais ne peut pas modifier.  
   * L'utilisateur a accès aux rubriques de type "Accès/Pas accès".
   */
  case ACCESS = 1;

  /**
   * Modification.
   *
   * L'utilisateur peut voir et modifier la rubrique.
   */
  case EDIT = 2;
}
