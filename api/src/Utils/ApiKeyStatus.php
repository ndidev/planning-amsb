<?php

namespace Api\Utils;

use ReflectionClass;

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


  /**
   * Renvoie les constantes de la présente classe.
   * 
   * @return array 
   */
  public static function getConstants()
  {
    $reflect = new ReflectionClass(__CLASS__);
    return $reflect->getConstants();
  }
}
