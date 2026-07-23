<?php

namespace App\Services\Export;

use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Lightweight export helper. CSV and printable HTML are supported now;
 * PDF/Excel can be layered on later behind the same call sites.
 */
class ExportService
{
    /**
     * @param  list<string>  $headers
     * @param  list<array<int, string|int|float|null>>  $rows
     */
    public function csv(string $filename, array $headers, array $rows): StreamedResponse
    {
        $filename = str_ends_with($filename, '.csv') ? $filename : "{$filename}.csv";

        return response()->streamDownload(function () use ($headers, $rows) {
            $out = fopen('php://output', 'w');
            if ($headers !== []) {
                fputcsv($out, $headers);
            }
            foreach ($rows as $row) {
                fputcsv($out, $row);
            }
            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }

    /**
     * Return a self-contained printable HTML document (browser "Print to PDF").
     */
    public function printableHtml(string $title, string $bodyHtml): Response
    {
        $safeTitle = e($title);
        $html = <<<HTML
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>{$safeTitle}</title>
<style>
  * { box-sizing: border-box; }
  body { font-family: -apple-system, Segoe UI, Roboto, sans-serif; color: #111; margin: 32px; }
  h1 { font-size: 20px; margin: 0 0 4px; }
  .meta { color: #666; font-size: 12px; margin-bottom: 20px; }
  table { width: 100%; border-collapse: collapse; font-size: 13px; }
  th, td { border: 1px solid #ccc; padding: 6px 8px; text-align: left; }
  th { background: #f3f4f6; }
  @media print { body { margin: 0; } .no-print { display: none; } }
</style>
</head>
<body>
<h1>{$safeTitle}</h1>
<div class="meta">Generated {$this->now()}</div>
{$bodyHtml}
<script>window.onload = function () { setTimeout(function () { window.print(); }, 250); };</script>
</body>
</html>
HTML;

        return response($html, 200, ['Content-Type' => 'text/html']);
    }

    /**
     * Build an HTML table from headers + rows for printableHtml().
     *
     * @param  list<string>  $headers
     * @param  list<array<int, string|int|float|null>>  $rows
     */
    public function htmlTable(array $headers, array $rows): string
    {
        $head = implode('', array_map(fn ($h) => '<th>'.e((string) $h).'</th>', $headers));
        $body = implode('', array_map(function ($row) {
            $cells = implode('', array_map(fn ($c) => '<td>'.e((string) $c).'</td>', $row));

            return "<tr>{$cells}</tr>";
        }, $rows));

        return "<table><thead><tr>{$head}</tr></thead><tbody>{$body}</tbody></table>";
    }

    private function now(): string
    {
        return now()->format('D, d M Y H:i');
    }
}
