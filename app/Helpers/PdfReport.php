<?php

namespace App\Helpers;

/**
 * Simple PDF report generator (no external dependencies).
 * Builds a valid PDF 1.4 document for analytics export.
 */
class PdfReport
{
    /**
     * @param array<string, string|int> $summary
     * @param array<int, array{month: string, cnt: int|string}> $regData
     */
    public static function analytics(string $title, array $summary, array $regData): string
    {
        $lines = [
            $title,
            'Generated: ' . date('Y-m-d H:i'),
            '',
            'Summary',
        ];
        foreach ($summary as $label => $value) {
            $lines[] = "{$label}: {$value}";
        }
        $lines[] = '';
        $lines[] = 'Registrations by Month (last 12 months)';
        $lines[] = 'Month          Count';
        $lines[] = str_repeat('-', 32);
        foreach ($regData as $row) {
            $lines[] = sprintf('%-14s %s', $row['month'] ?? '', $row['cnt'] ?? '0');
        }

        return self::fromLines($lines);
    }

    /** @param string[] $lines */
    public static function fromLines(array $lines): string
    {
        $content = "BT /F1 11 Tf 50 780 Td 14 TL\n";
        $y = 0;
        foreach ($lines as $i => $line) {
            $safe = self::escapePdfText($line);
            if ($i === 0) {
                $content .= "({$safe}) Tj\n";
            } else {
                $content .= "T* ({$safe}) Tj\n";
            }
            $y++;
            if ($y > 45) {
                break;
            }
        }
        $content .= "ET";

        $objects = [];
        $objects[] = "1 0 obj<< /Type /Catalog /Pages 2 0 R >>endobj\n";
        $objects[] = "2 0 obj<< /Type /Pages /Kids [3 0 R] /Count 1 >>endobj\n";
        $objects[] = "3 0 obj<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Contents 4 0 R /Resources << /Font << /F1 5 0 R >> >> >>endobj\n";
        $objects[] = '4 0 obj<< /Length ' . strlen($content) . " >>stream\n{$content}\nendstream\nendobj\n";
        $objects[] = "5 0 obj<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>endobj\n";

        $pdf = "%PDF-1.4\n";
        $offsets = [0];
        foreach ($objects as $obj) {
            $offsets[] = strlen($pdf);
            $pdf .= $obj;
        }
        $xrefPos = strlen($pdf);
        $pdf .= "xref\n0 " . (count($objects) + 1) . "\n";
        $pdf .= "0000000000 65535 f \n";
        for ($i = 1; $i <= count($objects); $i++) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$i]);
        }
        $pdf .= "trailer<< /Size " . (count($objects) + 1) . " /Root 1 0 R >>\n";
        $pdf .= "startxref\n{$xrefPos}\n%%EOF";

        return $pdf;
    }

    private static function escapePdfText(string $text): string
    {
        $text = mb_convert_encoding($text, 'ISO-8859-1', 'UTF-8');
        return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $text);
    }
}
