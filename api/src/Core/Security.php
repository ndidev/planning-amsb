<?php

namespace App\Core;

use App\Core\Database\Redis;
use App\Core\Constants;

/**
 * Security utilities.
 */
class Security
{
    /**
     * Waiting time before sending the response
     * to limit bruteforce attakcs.
     * 
     * Current value: 2 seconds.
     */
    private const SLEEP_TIME = 2 * Constants::ONE_SECOND;

    /**
     * Number of unauthenticated requests before blocking the IP address.
     * 
     * Current value: 100 attempts.
     */
    private const MAX_FAILED_ATTEMPTS = 100;

    /**
     * Timeframe to count unauthenticated requests.
     * 
     * Current value: 10 seconds.
     */
    public const FAILED_ATTEMPTS_TIMEOUT = 10 * Constants::ONE_SECOND;

    /**
     * IP address blocking duration
     * after too many unauthenticated requests.
     * 
     * Current value: 15 minutes.
     */
    public const BLOCKED_IP_TIMEOUT = 15 * Constants::ONE_MINUTE;

    /**
     * Redis instance.
     */
    private static ?Redis $redis = null;

    /**
     * Security measures to prevent bruteforce attacks.
     */
    public static function prevent_bruteforce(): void
    {
        $client_ip_address = $_SERVER["REMOTE_ADDR"];

        // Increment the number of connection attempts and, if need be, block the IP address
        $attempts = (int) static::redis()->incr("security:attempts:$client_ip_address");
        static::redis()->expire("security:attempts:$client_ip_address", static::FAILED_ATTEMPTS_TIMEOUT);

        if ($attempts >= static::MAX_FAILED_ATTEMPTS) {
            static::redis()->setex("security:blocked:$client_ip_address", static::BLOCKED_IP_TIMEOUT, "1");
        }

        sleep(static::SLEEP_TIME);
    }

    /**
     * Check if a request can be processed
     * (from a security point of view).
     * 
     * @return bool 
     */
    public static function check_if_request_can_be_done(): bool
    {
        if (static::is_ip_blocked() === true) {
            // return false;
        }

        return true;
    }

    /**
     * Check if the client IP address is blocked.
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
     * Returns (and, if necessary, create a new) Redis connection.
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
