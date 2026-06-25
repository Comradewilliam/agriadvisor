<?php

namespace App\Helpers;

class Avatar
{
    /**
     * Two-letter initials from a display name.
     */
    public static function initials(string $name): string
    {
        $parts = preg_split('/\s+/', trim($name), -1, PREG_SPLIT_NO_EMPTY);
        if (count($parts) >= 2) {
            return mb_strtoupper(mb_substr($parts[0], 0, 1) . mb_substr($parts[1], 0, 1));
        }
        if (count($parts) === 1) {
            return mb_strtoupper(mb_substr($parts[0], 0, 2));
        }
        return 'MK';
    }

    /**
     * Local avatar URL (SVG initials) — no external ui-avatars.com dependency.
     */
    public static function url(string $name, string $background = '154212', int $size = 80): string
    {
        $bg = preg_replace('/[^0-9a-fA-F]/', '', $background) ?: '154212';
        $size = max(16, min(256, $size));
        return '/assets/avatar.php?name=' . rawurlencode($name) . '&bg=' . $bg . '&size=' . $size;
    }
}
