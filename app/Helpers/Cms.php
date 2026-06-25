<?php

namespace App\Helpers;

use PDO;

class Cms
{
    /** Load farmer library articles from cms_content (admin-editable). */
    public static function farmerLibraryArticles(PDO $db): array
    {
        try {
            $stmt = $db->query("SELECT key_name, content_value FROM cms_content WHERE section = 'farmer_library' ORDER BY sort_order");
            $rows = $stmt->fetchAll();
        } catch (\Throwable $e) {
            return self::defaultArticles();
        }

        $byKey = [];
        foreach ($rows as $row) {
            $byKey[$row['key_name']] = $row['content_value'];
        }

        if (empty($byKey)) {
            return self::defaultArticles();
        }

        $articles = [];
        for ($i = 1; $i <= 8; $i++) {
            $title = trim($byKey["farmer_lib_{$i}_title"] ?? '');
            if ($title === '') {
                continue;
            }
            $articles[] = [
                'id'    => $i,
                'tag'   => $byKey["farmer_lib_{$i}_tag"] ?? 'MAKALA',
                'title' => $title,
                'body'  => $byKey["farmer_lib_{$i}_body"] ?? '',
                'img'   => $byKey["farmer_lib_{$i}_image"] ?? '/assets/images/fertilizer.png',
            ];
        }

        return $articles ?: self::defaultArticles();
    }

    private static function defaultArticles(): array
    {
        return [
            ['id' => 1, 'tag' => 'MWONGOZO', 'title' => 'Jinsi ya kutambua ukungu kwenye majani ya mahindi.', 'body' => '<p>Chunguza shamba kila wiki na tumia mbegu zilizothibitishwa.</p>', 'img' => '/assets/images/maize_disease.png'],
            ['id' => 2, 'tag' => 'VIDOKEZO', 'title' => 'Mbinu bora za umwagiliaji wakati wa kiangazi.', 'body' => '<p>Umwagilia asubuhi au jioni ili kupunguza uvujaji wa maji.</p>', 'img' => '/assets/images/irrigation_drip.png'],
            ['id' => 3, 'tag' => 'MBOLEA', 'title' => 'Aina 3 za mbolea kwa ukuaji wa kasi.', 'body' => '<p>Tumia DAP wakati wa kupanda, Urea wakati wa ukuaji, na CAN kabla ya maua.</p>', 'img' => '/assets/images/fertilizer.png'],
            ['id' => 4, 'tag' => 'MAVUNO', 'title' => 'Tayarisha ghala lako mwezi mmoja kabla.', 'body' => '<p>Kausha mazao vizuri na safisha ghala kabla ya kuhifadhi.</p>', 'img' => '/assets/images/harvest_storage.png'],
        ];
    }
}
