<?php

namespace App\Helpers;

/**
 * Lightweight markdown → HTML for chat messages (headers, bold, italic, lists, tables).
 */
class Markdown
{
    public static function toHtml(string $text): string
    {
        $text = self::normalizeInput($text);
        if ($text === '') {
            return '';
        }

        $lines = explode("\n", $text);
        $html = [];
        $inUl = false;
        $inOl = false;
        $tableRows = [];

        $closeList = function () use (&$html, &$inUl, &$inOl): void {
            if ($inUl) {
                $html[] = '</ul>';
                $inUl = false;
            }
            if ($inOl) {
                $html[] = '</ol>';
                $inOl = false;
            }
        };

        $flushTable = function () use (&$html, &$tableRows): void {
            if (empty($tableRows)) {
                return;
            }
            $html[] = self::renderTable($tableRows);
            $tableRows = [];
        };

        foreach ($lines as $line) {
            $trimmed = trim($line);

            if ($trimmed === '') {
                $closeList();
                $flushTable();
                $html[] = '<br class="chat-md-break" aria-hidden="true">';
                continue;
            }

            if (str_contains($trimmed, '|') && preg_match('/^(\|?.+\|.+|\|.+\|)$/', $trimmed)) {
                if (preg_match('/^[\|\s:\-]+$/', $trimmed)) {
                    continue;
                }
                $closeList();
                $cells = array_values(array_filter(
                    array_map('trim', explode('|', trim($trimmed, '|'))),
                    fn($c) => $c !== ''
                ));
                if ($cells) {
                    $tableRows[] = $cells;
                }
                continue;
            }

            if (!empty($tableRows)) {
                $flushTable();
            }

            if (preg_match('/^(#{1,3})\s+(.+)$/', $trimmed, $m)) {
                $closeList();
                $tag = match (strlen($m[1])) {
                    1 => 'h3',
                    2 => 'h4',
                    default => 'h5',
                };
                $html[] = '<' . $tag . ' class="chat-md-heading">' . self::inline($m[2]) . '</' . $tag . '>';
                continue;
            }

            if (preg_match('/^[\*\-]\s+(.+)$/', $trimmed, $m)) {
                if ($inOl) {
                    $html[] = '</ol>';
                    $inOl = false;
                }
                if (!$inUl) {
                    $html[] = '<ul class="chat-md-list">';
                    $inUl = true;
                }
                $html[] = '<li>' . self::inline($m[1]) . '</li>';
                continue;
            }

            if (preg_match('/^\d+\.\s+(.+)$/', $trimmed, $m)) {
                if ($inUl) {
                    $html[] = '</ul>';
                    $inUl = false;
                }
                if (!$inOl) {
                    $html[] = '<ol class="chat-md-list">';
                    $inOl = true;
                }
                $html[] = '<li>' . self::inline($m[1]) . '</li>';
                continue;
            }

            $closeList();
            $html[] = '<p class="chat-md-p">' . self::inline($trimmed) . '</p>';
        }

        $closeList();
        $flushTable();

        return implode("\n", $html);
    }

    /** @param array<int, array<int, string>> $rows */
    private static function renderTable(array $rows): string
    {
        $html = '<div class="chat-md-table-wrap"><table class="chat-md-table"><tbody>';
        foreach ($rows as $i => $cells) {
            $html .= '<tr>';
            foreach ($cells as $cell) {
                $tag = $i === 0 ? 'th' : 'td';
                $html .= '<' . $tag . '>' . self::inline($cell) . '</' . $tag . '>';
            }
            $html .= '</tr>';
        }
        $html .= '</tbody></table></div>';
        return $html;
    }

    private static function normalizeInput(string $text): string
    {
        $text = str_replace(["\r\n", "\r"], "\n", $text);
        $text = preg_replace('/<br\s*\/?>/i', "\n", $text) ?? $text;
        $text = preg_replace('/&lt;br\s*\/?&gt;/i', "\n", $text) ?? $text;
        return trim($text);
    }

    private static function inline(string $s): string
    {
        $s = preg_replace('/<br\s*\/?>/i', "\n", $s) ?? $s;
        $s = preg_replace('/&lt;br\s*\/?&gt;/i', "\n", $s) ?? $s;
        $s = htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
        $s = preg_replace('/\*\*(.+?)\*\*/s', '<strong>$1</strong>', $s) ?? $s;
        $s = preg_replace('/__(.+?)__/s', '<strong>$1</strong>', $s) ?? $s;
        $s = preg_replace('/(?<!\*)\*(?!\*)(.+?)(?<!\*)\*(?!\*)/s', '<em>$1</em>', $s) ?? $s;
        $s = preg_replace('/(?<!_)_(?!_)(.+?)(?<!_)_(?!_)/s', '<em>$1</em>', $s) ?? $s;
        return str_replace("\n", '<br>', $s);
    }
}
