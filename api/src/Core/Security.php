<?php

namespace App\Core;

use App\Core\Database\Redis;

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
    private const SLEEP_TIME = 2 * ONE_SECOND;

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
    public const FAILED_ATTEMPTS_TIMEOUT = 10 * ONE_SECOND;

    /**
     * IP address blocking duration
     * after too many unauthenticated requests.
     * 
     * Current value: 15 minutes.
     */
    public const BLOCKED_IP_TIMEOUT = 15 * ONE_MINUTE;

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
        $attempts = (int) static::getRedisInstance()->incr("security:attempts:$client_ip_address");
        static::getRedisInstance()->expire("security:attempts:$client_ip_address", static::FAILED_ATTEMPTS_TIMEOUT);

        if ($attempts >= static::MAX_FAILED_ATTEMPTS) {
            static::getRedisInstance()->setex("security:blocked:$client_ip_address", static::BLOCKED_IP_TIMEOUT, "1");
        }

        sleep(static::SLEEP_TIME);
    }

    /**
     * Check if a request can be processed
     * (from a security point of view).
     * 
     * @return bool 
     */
    public static function checkIfRequestCanBeDone(): bool
    {
        if (static::isIpBlocked() === true) {
            // return false;
        }

        return true;
    }

    /**
     * Check if the client IP address is blocked.
     */
    private static function isIpBlocked(): bool
    {
        $client_ip_address = $_SERVER["REMOTE_ADDR"];

        if (static::getRedisInstance()->get("security:blocked:$client_ip_address")) {
            static::getRedisInstance()->expire("security:blocked:$client_ip_address", static::BLOCKED_IP_TIMEOUT);
            return true;
        }

        return false;
    }

    /**
     * Returns (and, if necessary, create a new) Redis connection.
     * 
     * @return Redis 
     */
    private static function getRedisInstance(): Redis
    {
        if (!static::$redis) {
            static::$redis = new Redis();
        }

        return static::$redis;
    }
}
