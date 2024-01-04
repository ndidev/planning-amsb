<?php

namespace App\Core;

use App\Core\Database\Redis;

/**
 * Utilitaires de sécurité.
 */
class Security
{
  /**
   * Temps d'attente avant l'envoi de la réponse
   * pour limiter les attaques par force brute.
   */
  private const SLEEP_TIME = 2;

  /**
   * Nombre de requêtes non authentifiées avant de bloquer l'IP.
   */
  private const MAX_FAILED_ATTEMPTS = 100;

  /**
   * Fenêtre de temps pour le comptage des requêtes non authentifiées.
   */
  public const FAILED_ATTEMPTS_TIMEOUT = 10;

  /**
   * Durée de blocage d'une adresse IP
   * après un nombre trop important de requêtes non authentifiées.
   * 
   * Valeur actuelle : 15 minutes.
   */
  public const BLOCKED_IP_TIMEOUT = 15 * UNE_MINUTE;

  /**
   * Instance Redis.
   */
  private static ?Redis $redis = null;

  /**
   * Mesures pour limiter les attaques par force brute
   * lors d'une requête.
   */
  public static function prevent_bruteforce(): void
  {
    $client_ip_address = $_SERVER["REMOTE_ADDR"];

    // Incrémenter le nombre de tentatives de connexion et blocage éventuel
    $attempts = (int) static::redis()->incr("security:attempts:$client_ip_address");
    static::redis()->expire("security:attempts:$client_ip_address", static::FAILED_ATTEMPTS_TIMEOUT);

    if ($attempts >= static::MAX_FAILED_ATTEMPTS) {
      static::redis()->setex("security:blocked:$client_ip_address", static::BLOCKED_IP_TIMEOUT, "1");
    }

    sleep(static::SLEEP_TIME);
  }

  /**
   * Vérifie si une requête peut être effectuée
   * (d'un point de vue de la sécurité).
   * 
   * @return bool 
   */
  public static function check_if_request_can_be_done(): bool
  {
    if (static::is_ip_blocked() === true) {
      return false;
    }

    return true;
  }

  /**
   * Vérifie si l'adresse IP du client est bloquée.
   */
  private static function is_ip_blocked(): bool
  {
    $client_ip_address = $_SERVER["REMOTE_ADDR"];

    if (static::redis()->get("security:blocked:$client_ip_address")) {
      static::redis()->expire("security:blocked:$client_ip_address", static::BLOCKED_IP_TIMEOUT);
      return true;
    }

    return false;
  }

  /**
   * Renvoie (et, si nécessaire, instancie) une connexion à Redis.
   * 
   * @return Redis 
   */
  private static function redis(): Redis
  {
    if (!static::$redis) {
      static::$redis = new Redis();
    }

    return static::$redis;
  }
}
