<?php

namespace App\Helpers;

/**
 * Formatting helpers for chat_threads sidebar.
 */
class ChatThreads
{
    public static function formatSidebarRow(array $thread): array
    {
        $preview = $thread['title'] ?? $thread['first_message'] ?? 'Mazungumzo';
        $preview = mb_substr($preview, 0, 50) . (mb_strlen($preview) > 50 ? '…' : '');
        $when = $thread['updated_at'] ?? $thread['started_at'] ?? date('Y-m-d H:i:s');

        return [
            'id'      => (int)$thread['id'],
            'preview' => $preview,
            'label'   => self::formatLabel($when),
        ];
    }

    public static function formatLabel(string $datetime): string
    {
        $day = date('Y-m-d', strtotime($datetime));
        if ($day === date('Y-m-d')) {
            return 'Leo · ' . date('H:i', strtotime($datetime));
        }
        if ($day === date('Y-m-d', strtotime('-1 day'))) {
            return 'Jana · ' . date('H:i', strtotime($datetime));
        }
        return date('d M Y · H:i', strtotime($datetime));
    }
}
