<?php

namespace App\Services;

use App\Core\Database;
use App\Helpers\Logger;

/**
 * Weather via WeatherAPI.com — https://www.weatherapi.com/docs/
 */
class WeatherService
{
    private ?string $apiKey;
    private string $baseUrl = 'https://api.weatherapi.com/v1';
    private string $cacheDir;

    public function __construct()
    {
        $config = require __DIR__ . '/../../config.php';
        $this->apiKey = $config['apis']['weatherapi']['api_key'] ?: null;
        $this->cacheDir = dirname(__DIR__, 2) . '/storage/weather_cache';
        if (!is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0775, true);
        }
    }

    public function getWeather($lat, $lon)
    {
        if (!$this->apiKey) {
            Logger::warning('WeatherAPI key missing');
            return null;
        }

        $cacheKey = 'current_' . round((float)$lat, 2) . '_' . round((float)$lon, 2);
        $cached = $this->readCache($cacheKey, 1800);
        if ($cached !== null) {
            return $cached;
        }

        $q = urlencode("{$lat},{$lon}");
        $url = "{$this->baseUrl}/current.json?key={$this->apiKey}&q={$q}";
        $raw = $this->fetchJson($url);

        if ($raw !== null) {
            $normalized = $this->normalizeCurrent($raw);
            $this->writeCache($cacheKey, $normalized);
            return $normalized;
        }

        $stale = $this->readCache($cacheKey, 86400, true);
        if ($stale !== null) {
            Logger::warning('Using stale weather cache', ['lat' => $lat, 'lon' => $lon]);
            return $stale;
        }

        return $this->getSeasonalFallback($lat, $lon);
    }

    public function getForecast($lat, $lon, $days = 7)
    {
        if (!$this->apiKey) {
            return null;
        }

        $days = min(max((int)$days, 1), 7);
        $cacheKey = 'forecast_' . round((float)$lat, 2) . '_' . round((float)$lon, 2) . "_{$days}";
        $cached = $this->readCache($cacheKey, 3600);
        if ($cached !== null) {
            return $cached;
        }

        $q = urlencode("{$lat},{$lon}");
        $url = "{$this->baseUrl}/forecast.json?key={$this->apiKey}&q={$q}&days={$days}";
        $raw = $this->fetchJson($url);

        if ($raw !== null) {
            $normalized = $this->normalizeForecast($raw);
            $this->writeCache($cacheKey, $normalized);
            return $normalized;
        }

        $stale = $this->readCache($cacheKey, 86400, true);
        if ($stale !== null) {
            Logger::warning('Using stale forecast cache', ['lat' => $lat, 'lon' => $lon]);
            return $stale;
        }

        return $this->buildSyntheticForecast($lat, $lon);
    }

    public function getWeatherByVillage($villageIdOrName): array
    {
        $db = Database::getInstance()->getConnection();
        $village = $this->resolveVillage($db, $villageIdOrName);

        if (!$village) {
            Logger::warning('Village not found for weather lookup', ['input' => $villageIdOrName]);
            return $this->wrapFallback(null, -6.8234, 31.0436);
        }

        $lat = $village['lat'] ?: -6.8234;
        $lng = $village['lng'] ?: 31.0436;

        $current = $this->getWeather($lat, $lng);
        $forecast = $this->getForecast($lat, $lng, 7);

        $usingFallback = ($current['fallback'] ?? false) || ($forecast['fallback'] ?? false);

        return [
            'village'  => $village,
            'current'  => $current,
            'forecast' => $forecast,
            'source'   => $usingFallback ? 'fallback' : 'live',
        ];
    }

    /** Normalize WeatherAPI current.json to legacy shape used by views. */
    private function normalizeCurrent(array $raw): array
    {
        $c = $raw['current'] ?? [];
        $code = (int)($c['condition']['code'] ?? 1003);
        $isDay = (int)($c['is_day'] ?? 1);

        return [
            'main' => [
                'temp'     => $c['temp_c'] ?? 25,
                'temp_min' => ($c['temp_c'] ?? 25) - 2,
                'temp_max' => ($c['temp_c'] ?? 25) + 2,
                'humidity' => $c['humidity'] ?? 65,
                'pressure' => $c['pressure_mb'] ?? 1013,
            ],
            'weather' => [[
                'description' => $c['condition']['text'] ?? 'Hali ya hewa',
                'icon'        => $this->mapConditionCode($code, $isDay),
            ]],
            'wind' => [
                'speed' => round(($c['wind_kph'] ?? 0) / 3.6, 1),
            ],
            'visibility' => (int)(($c['vis_km'] ?? 10) * 1000),
            'fallback'   => false,
        ];
    }

    /** Normalize WeatherAPI forecast.json to legacy list shape used by views. */
    private function normalizeForecast(array $raw): array
    {
        $list = [];
        foreach ($raw['forecast']['forecastday'] ?? [] as $day) {
            $code = (int)($day['day']['condition']['code'] ?? 1003);
            $rainChance = (int)($day['day']['daily_chance_of_rain'] ?? 0);
            $list[] = [
                'dt'   => (int)($day['date_epoch'] ?? strtotime($day['date'] ?? 'now')),
                'main' => [
                    'temp_max' => $day['day']['maxtemp_c'] ?? 26,
                    'temp_min' => $day['day']['mintemp_c'] ?? 20,
                ],
                'weather' => [[
                    'description' => $day['day']['condition']['text'] ?? '',
                    'icon'        => $this->mapConditionCode($code, 1),
                ]],
                'pop'  => $rainChance / 100,
                'rain' => ['3h' => ($day['day']['totalprecip_mm'] ?? 0) / 8],
            ];
        }

        return ['list' => $list, 'fallback' => false];
    }

    private function resolveVillage($db, $villageIdOrName): ?array
    {
        if (is_numeric($villageIdOrName)) {
            $stmt = $db->prepare("
                SELECT v.*, w.name AS ward_name, d.name AS district_name
                FROM villages v
                LEFT JOIN wards w ON w.id = v.ward_id
                LEFT JOIN districts d ON d.id = w.district_id
                WHERE v.id = ? LIMIT 1
            ");
            $stmt->execute([$villageIdOrName]);
            return $stmt->fetch() ?: null;
        }

        $stmt = $db->prepare("
            SELECT v.*, w.name AS ward_name, d.name AS district_name
            FROM villages v
            LEFT JOIN wards w ON w.id = v.ward_id
            LEFT JOIN districts d ON d.id = w.district_id
            WHERE v.name LIKE ? LIMIT 1
        ");
        $stmt->execute(["%{$villageIdOrName}%"]);
        return $stmt->fetch() ?: null;
    }

    private function fetchJson(string $url): ?array
    {
        $ch = curl_init($url);
        $opts = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT      => 15,
            CURLOPT_CONNECTTIMEOUT => 8,
        ];
        if (getenv('APP_ENV') === 'local' || str_contains(getenv('APP_URL') ?: '', '127.0.0.1')) {
            $opts[CURLOPT_SSL_VERIFYPEER] = false;
            $opts[CURLOPT_SSL_VERIFYHOST] = 0;
        }
        curl_setopt_array($ch, $opts);

        $response = curl_exec($ch);
        $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            Logger::error('WeatherAPI curl error', ['error' => $error]);
            return null;
        }

        if ($httpCode !== 200) {
            Logger::warning('WeatherAPI non-200 response', ['http_code' => $httpCode]);
            return null;
        }

        $data = json_decode($response, true);
        return is_array($data) ? $data : null;
    }

    private function readCache(string $key, int $maxAgeSeconds, bool $allowExpired = false): ?array
    {
        $file = $this->cacheDir . '/' . md5($key) . '.json';
        if (!file_exists($file)) {
            return null;
        }

        $payload = json_decode(file_get_contents($file), true);
        if (!is_array($payload) || !isset($payload['saved_at'], $payload['data'])) {
            return null;
        }

        $age = time() - (int)$payload['saved_at'];
        if (!$allowExpired && $age > $maxAgeSeconds) {
            return null;
        }

        return $payload['data'];
    }

    private function writeCache(string $key, array $data): void
    {
        $file = $this->cacheDir . '/' . md5($key) . '.json';
        file_put_contents($file, json_encode([
            'saved_at' => time(),
            'data'     => $data,
        ]));
    }

    private function getSeasonalFallback(float $lat, float $lon): array
    {
        Logger::info('Serving seasonal weather fallback', ['lat' => $lat, 'lon' => $lon]);
        $month = (int)date('n');
        $temp = match (true) {
            $month >= 3 && $month <= 6 => 24,
            $month >= 10 && $month <= 12 => 26,
            default => 28,
        };

        return [
            'main' => ['temp' => $temp, 'temp_min' => $temp - 3, 'temp_max' => $temp + 2, 'humidity' => 65, 'pressure' => 1013],
            'weather' => [['description' => 'Hali ya hewa ya kawaida (makadirio ya msimu)', 'icon' => '03d']],
            'wind' => ['speed' => 2.5],
            'visibility' => 10000,
            'fallback' => true,
            'fallback_reason' => 'api_unavailable',
        ];
    }

    private function buildSyntheticForecast(float $lat, float $lon): array
    {
        $list = [];
        $base = $this->getSeasonalFallback($lat, $lon);
        for ($i = 0; $i < 7; $i++) {
            $dt = strtotime("+{$i} day noon");
            $list[] = [
                'dt' => $dt,
                'main' => $base['main'],
                'weather' => $base['weather'],
                'rain' => ['3h' => ($i % 3 === 0) ? 1.2 : 0],
                'pop' => ($i % 3 === 0) ? 0.4 : 0.1,
            ];
        }
        return ['list' => $list, 'fallback' => true];
    }

    private function wrapFallback(?array $village, float $lat, float $lng): array
    {
        return [
            'village'  => $village ?? ['name' => 'Kakonko', 'ward_name' => 'Kakonko', 'district_name' => 'Kigoma'],
            'current'  => $this->getSeasonalFallback($lat, $lng),
            'forecast' => $this->buildSyntheticForecast($lat, $lng),
            'source'   => 'fallback',
        ];
    }

    public function buildOfficerWeekForecast(float $lat = -6.8234, float $lon = 31.0436): array
    {
        $raw = $this->getForecast($lat, $lon, 7);
        if (!$raw || empty($raw['list'])) {
            $raw = $this->buildSyntheticForecast($lat, $lon);
        }

        $swahiliDays = ['Jumapili', 'Jumatatu', 'Jumanne', 'Jumatano', 'Alhamisi', 'Ijumaa', 'Jumamosi'];
        $forecast = [];
        $idx = 0;

        foreach (array_slice($raw['list'], 0, 7) as $item) {
            $dow = (int)date('w', $item['dt']);
            $rainPct = isset($item['pop']) ? (int)round($item['pop'] * 100) : min(100, (int)round(($item['rain']['3h'] ?? 0) * 20));
            $forecast[] = [
                'day'      => $idx === 0 ? 'Leo' : $swahiliDays[$dow],
                'date'     => date('d M Y', $item['dt']),
                'temp_h'   => (int)round($item['main']['temp_max']),
                'temp_l'   => (int)round($item['main']['temp_min']),
                'rain'     => $rainPct,
                'icon'     => $this->mapOwmIconToMaterial($item['weather'][0]['icon'] ?? '03d'),
                'detail'   => ucfirst($item['weather'][0]['description'] ?? ''),
                'featured' => $idx === 0,
            ];
            $idx++;
        }

        if (empty($forecast)) {
            for ($i = 0; $i < 7; $i++) {
                $dt = strtotime("+{$i} day");
                $dow = (int)date('w', $dt);
                $forecast[] = [
                    'day'      => $i === 0 ? 'Leo' : $swahiliDays[$dow],
                    'date'     => date('d M Y', $dt),
                    'temp_h'   => 26,
                    'temp_l'   => 20,
                    'rain'     => 20,
                    'icon'     => 'wb_cloudy',
                    'detail'   => 'Hali ya hewa ya kawaida',
                    'featured' => $i === 0,
                ];
            }
        }

        return $forecast;
    }

    /** Map WeatherAPI condition code to OWM-style icon suffix for existing views. */
    private function mapConditionCode(int $code, int $isDay): string
    {
        $suffix = $isDay ? 'd' : 'n';
        if ($code === 1000) {
            return '01' . $suffix;
        }
        if (in_array($code, [1003, 1006], true)) {
            return '03' . $suffix;
        }
        if ($code === 1009) {
            return '04' . $suffix;
        }
        if (in_array($code, [1063, 1180, 1183, 1186, 1189, 1192, 1195, 1240, 1243, 1246], true)) {
            return '10' . $suffix;
        }
        if (in_array($code, [1087, 1273, 1276, 1279, 1282], true)) {
            return '11' . $suffix;
        }
        if (in_array($code, [1066, 1210, 1213, 1216, 1219, 1222, 1225, 1255, 1258], true)) {
            return '13' . $suffix;
        }
        if (in_array($code, [1030, 1135, 1147], true)) {
            return '50' . $suffix;
        }
        return '02' . $suffix;
    }

    private function mapOwmIconToMaterial(string $owmIcon): string
    {
        $map = [
            '01d' => 'wb_sunny', '01n' => 'nightlight',
            '02d' => 'partly_cloudy_day', '02n' => 'partly_cloudy_night',
            '03d' => 'wb_cloudy', '03n' => 'wb_cloudy',
            '04d' => 'cloud', '04n' => 'cloud',
            '09d' => 'rainy', '09n' => 'rainy',
            '10d' => 'rainy', '10n' => 'rainy',
            '11d' => 'thunderstorm', '11n' => 'thunderstorm',
            '13d' => 'ac_unit', '13n' => 'ac_unit',
            '50d' => 'foggy', '50n' => 'foggy',
        ];
        return $map[$owmIcon] ?? 'wb_cloudy';
    }

    /**
     * Build a short Swahili weather alert for the crops page.
     */
    public function buildCropAlert(array $weatherBundle): array
    {
        $current = $weatherBundle['current'] ?? [];
        $temp = (int)round($current['main']['temp'] ?? 28);
        $desc = $current['weather'][0]['description'] ?? 'Hali ya hewa ya kawaida';

        $tomorrowHigh = $temp;
        $tomorrowRain = 0;
        $list = $weatherBundle['forecast']['list'] ?? [];
        if (count($list) > 1) {
            $tomorrowHigh = (int)round($list[1]['main']['temp_max'] ?? $temp);
            $tomorrowRain = (int)round(($list[1]['pop'] ?? 0) * 100);
        } elseif (!empty($list[0])) {
            $tomorrowHigh = (int)round($list[0]['main']['temp_max'] ?? $temp);
            $tomorrowRain = (int)round(($list[0]['pop'] ?? 0) * 100);
        }

        if ($tomorrowHigh >= 33) {
            return [
                'temp'    => $tomorrowHigh,
                'message' => 'Kuna uwezekano wa joto kali kesho (' . $tomorrowHigh . '°C). Hakikisha umwagiliaji unatosha ili kuzuia mimea kunyauka.',
                'level'   => 'heat',
            ];
        }

        if ($tomorrowRain >= 60) {
            return [
                'temp'    => $temp,
                'message' => 'Mvua kubwa inatarajiwa kesho (' . $tomorrowRain . '%). Epuka kupanda mbolea au dawa sasa; angalia mifereji ya maji.',
                'level'   => 'rain',
            ];
        }

        if ($tomorrowHigh >= 30) {
            return [
                'temp'    => $tomorrowHigh,
                'message' => 'Joto la wastani-juu kesho. Ongeza umwagiliaji wa asubuhi na funika udongo kwa malisho ikiwezekana.',
                'level'   => 'warm',
            ];
        }

        return [
            'temp'    => $temp,
            'message' => ucfirst($desc) . '. Hali ya hewa inafaa kwa shughuli za shambani leo.',
            'level'   => 'normal',
        ];
    }
}
