<?php

namespace App\SatuSehat\Support;

class DateHelper
{
    /**
     * Safely parse a datetime string to ISO 8601 format with timezone offset.
     * Returns null for empty, zero-date (0000-*), or Unix epoch (1970-01-01) values.
     */
    public static function safeDateTime(?string $value): ?string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        if (str_starts_with($value, '0000') || str_starts_with($value, '1970-01-01')) {
            return null;
        }

        $ts = strtotime($value);

        if ($ts === false) {
            return null;
        }

        return date('Y-m-d\TH:i:sP', $ts);
    }
}
