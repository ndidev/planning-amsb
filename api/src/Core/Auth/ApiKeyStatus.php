<?php

namespace App\Core\Auth;

/**
 * Status of API keys.
 */
final class ApiKeyStatus
{
    /**
     * Active key.
     * 
     * The key can be used normally.
     */
    public const ACTIVE = "active";

    /**
     * Expired key.
     */
    public const EXPIRED = "expired";

    /**
     * Revoked key.
     */
    public const REVOKED = "revoked";
}
