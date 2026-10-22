<?php

namespace App\Support\Csv;

use InvalidArgumentException;

class CsvReader
{
    /**
     * @param  array<string, string>  $headerAliases  normalized header => canonical field
     * @return list<array{line:int, data:array<string, string>}>
     */
    public function read(string $path, array $headerAliases, array $requiredHeaders = []): array
    {
        $handle = fopen($path, 'r');
        if ($handle === false) {
            throw new InvalidArgumentException('Unable to open CSV file.');
        }

        try {
            $headers = null;
            $rows = [];
            $line = 0;

            while (($data = fgetcsv($handle)) !== false) {
                $line++;
                $data = array_map(fn ($value) => is_string($value) ? trim($value) : (string) ($value ?? ''), $data);
                $isEmpty = count(array_filter($data, fn ($value) => $value !== '')) === 0;
                if ($isEmpty) {
                    continue;
                }

                if ($headers === null) {
                    $headers = array_map(fn ($header) => $this->normalizeHeader((string) $header), $data);
                    $mapped = [];
                    foreach ($headers as $header) {
                        if (isset($headerAliases[$header])) {
                            $mapped[] = $headerAliases[$header];
                        }
                    }

                    if ($mapped === []) {
                        throw new InvalidArgumentException('CSV header row is missing recognized columns.');
                    }

                    foreach ($requiredHeaders as $required) {
                        if (! in_array($required, $mapped, true)) {
                            throw new InvalidArgumentException("CSV is missing required column: {$required}");
                        }
                    }

                    continue;
                }

                $rowData = [];
                foreach ($headers as $index => $header) {
                    if (! array_key_exists($index, $data)) {
                        continue;
                    }
                    $canonical = $headerAliases[$header] ?? null;
                    if ($canonical === null) {
                        continue;
                    }
                    $rowData[$canonical] = $data[$index];
                }

                if (count(array_filter($rowData, fn ($value) => $value !== '')) === 0) {
                    continue;
                }

                $rows[] = [
                    'line' => $line,
                    'data' => $rowData,
                ];
            }

            return $rows;
        } finally {
            fclose($handle);
        }
    }

    public function normalizeHeader(string $header): string
    {
        $header = strtolower(trim($header));
        $header = str_replace([' ', '-', '/'], '_', $header);

        return preg_replace('/_+/', '_', $header) ?: $header;
    }
}
