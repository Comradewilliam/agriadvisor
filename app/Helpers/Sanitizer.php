<?php

namespace App\Helpers;

class Sanitizer
{
    public static function string($value, $maxLength = 255)
    {
        $value = trim((string)$value);
        $value = strip_tags($value);
        $value = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
        return mb_substr($value, 0, $maxLength, 'UTF-8');
    }

    public static function textarea($value, $maxLength = 2000)
    {
        $value = trim((string)$value);
        $value = strip_tags($value);
        return mb_substr($value, 0, $maxLength, 'UTF-8');
    }

    public static function int($value)
    {
        $v = filter_var($value, FILTER_VALIDATE_INT);
        return $v === false ? null : (int)$v;
    }

    public static function float($value)
    {
        if ($value === null || $value === '') {
            return null;
        }
        return is_numeric($value) ? (float)$value : null;
    }

    public static function email($value)
    {
        $value = trim((string)$value);
        return filter_var($value, FILTER_VALIDATE_EMAIL) ? $value : null;
    }

    public static function phone($value)
    {
        $value = preg_replace('/[^0-9+]/', '', (string)$value);
        return mb_substr($value, 0, 20);
    }

    /**
     * Normalize Tanzania mobile to local format 0XXXXXXXXX (matches farmers.phone storage).
     */
    public static function normalizePhone(string $phone): string
    {
        $digits = preg_replace('/\D/', '', trim($phone));
        if (str_starts_with($digits, '255') && strlen($digits) >= 12) {
            return '0' . substr($digits, 3);
        }
        if (str_starts_with($digits, '0')) {
            return $digits;
        }
        return $digits;
    }

    /**
     * Tanzania mobile in E.164 for Africa's Talking (+255XXXXXXXXX).
     */
    public static function toInternational(string $phone, string $countryCode = '255'): string
    {
        $digits = preg_replace('/\D/', '', trim($phone));
        if ($digits === '') {
            return '';
        }
        if (str_starts_with($digits, $countryCode)) {
            return '+' . $digits;
        }
        if (str_starts_with($digits, '0')) {
            return '+' . $countryCode . substr($digits, 1);
        }
        return '+' . $countryCode . $digits;
    }
}
