<?php

// Path: api/src/Core/HTTP/ETag.php

declare(strict_types=1);

namespace App\Core\HTTP;

/**
 * ETag
 */
abstract class ETag
{
    /**
     * Renvoie un hash MD5 des données passées en paramètre.
     * 
     * @param mixed $data Données.
     * @param bool  $weak Si ```TRUE```, renvoie un ETag faible.
     * 
     * @return string ETag (chaîne MD5).
     */
    public static function get(mixed $data, ?bool $weak = false): string
    {
        $etag = '"' . hash("md5", serialize($data)) . '"';

        if ($weak) {
            $etag = "W/" . $etag;
        }

        return $etag;
    }
}
