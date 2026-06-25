<?php
/**
 * app/Helpers/Lang.php
 * Lightweight translation helper.
 *
 * Usage:
 *   Lang::init();          // call once in layout/bootstrap
 *   __('key')              // returns translated string
 *   Lang::get('key')       // same
 *   Lang::set('sw')        // switch locale
 */
namespace App\Helpers {

class Lang
{
    private static array  $strings  = [];
    private static string $locale   = 'en';

    /** Boot from session (call once in layout) */
    public static function init(): void
    {
        // Determine locale
        $role = $_SESSION['role'] ?? '';

        // Farmer always Swahili, super_admin always English
        if ($role === 'farmer') {
            self::$locale = 'sw';
        } elseif ($role === 'super_admin') {
            self::$locale = 'en';
        } else {
            // WAO / DAO: respect session preference, default English
            self::$locale = $_SESSION['lang'] ?? 'en';
        }

        self::load(self::$locale);
    }

    /** Switch locale (saves to session, reloads strings) */
    public static function set(string $locale): void
    {
        if (!in_array($locale, ['en', 'sw'], true)) {
            $locale = 'en';
        }
        $_SESSION['lang'] = $locale;
        self::$locale = $locale;
        self::load($locale);
    }

    public static function getLocale(): string
    {
        return self::$locale;
    }

    /** Translate a key, with optional :placeholder replacement */
    public static function get(string $key, array $replace = []): string
    {
        $text = self::$strings[$key] ?? $key;
        foreach ($replace as $placeholder => $value) {
            $text = str_replace(':' . $placeholder, $value, $text);
        }
        return $text;
    }

    private static function load(string $locale): void
    {
        $file = dirname(__DIR__, 2) . '/lang/' . $locale . '.php';
        self::$strings = file_exists($file) ? require $file : [];
    }
}

}

namespace {
    /** Global translation shortcut */
    function __(string $key, array $replace = []): string
    {
        return \App\Helpers\Lang::get($key, $replace);
    }
}
