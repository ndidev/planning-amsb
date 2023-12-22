<?php

namespace App\Core\Auth;

/**
 * Statuts des clés d'API.
 */
final class ApiKeyStatus
{
  /**
   * Clé active.
   * 
   * La clé peut être utilisée normalement.
   */
  public const ACTIVE = "active";

  /**
   * Clé expirée.
   */
  public const EXPIRED = "expired";

  /**
   * Clé révoquée.
   */
  public const REVOKED = "revoked";
}
