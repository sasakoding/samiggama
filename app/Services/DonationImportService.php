<?php

namespace App\Services;

use App\Models\Donation;
use App\Models\DonationProgram;
use Illuminate\Support\Str;
use ZipArchive;

class DonationImportService
{
    /**
     * Import rows from an uploaded Excel (.xlsx), CSV, or tabular file.
     *
     * @param string $filePath
     * @return array{imported: int, created_programs: array, errors: array}
     */
    public function import(string $filePath): array
    {
        $rows = $this->extractRows($filePath);

        if (empty($rows)) {
            return [
                'imported' => 0,
                'created_programs' => [],
                'errors' => ['File kosong atau format data tidak dapat dibaca.'],
            ];
        }

        // Determine column mapping
        $mapping = $this->determineColumnMapping($rows[0]);
        $startIndex = $mapping['is_header'] ? 1 : 0;

        $importedCount = 0;
        $createdPrograms = [];
        $errors = [];

        for ($i = $startIndex; $i < count($rows); $i++) {
            $row = $rows[$i];

            // Skip empty rows
            if (empty(array_filter($row, fn($val) => trim((string) $val) !== ''))) {
                continue;
            }

            $kegiatanRaw = trim((string) ($row[$mapping['kegiatan']] ?? ''));
            $namaRaw = trim((string) ($row[$mapping['nama']] ?? ''));
            $jumlahRaw = (string) ($row[$mapping['jumlah']] ?? '');

            // Skip if Kegiatan or Nama is completely missing
            if ($kegiatanRaw === '' && $namaRaw === '') {
                continue;
            }

            // Clean amount
            $cleanAmount = (int) preg_replace('/[^0-9]/', '', $jumlahRaw);
            if ($cleanAmount <= 0) {
                $errors[] = "Baris " . ($i + 1) . " diabaikan: nominal jumlah tidak valid.";
                continue;
            }

            // Find or automatically create DonationProgram
            $programName = $kegiatanRaw ?: 'Program Dāna Umum';
            $program = DonationProgram::whereRaw('LOWER(title) = ?', [strtolower($programName)])->first();

            if (!$program) {
                $baseSlug = Str::slug($programName) ?: 'program-dana';
                $slug = $baseSlug;
                $counter = 1;
                while (DonationProgram::where('slug', $slug)->exists()) {
                    $slug = $baseSlug . '-' . time() . '-' . $counter;
                    $counter++;
                }

                $program = DonationProgram::create([
                    'title' => $programName,
                    'slug' => $slug,
                    'category' => 'Pembangunan & Sarana',
                    'target_amount' => 0,
                    'start_date' => now()->toDateString(),
                    'status' => 'aktif',
                    'cover_image' => 'images/gallery-altar.jpg',
                ]);

                if (!in_array($programName, $createdPrograms)) {
                    $createdPrograms[] = $programName;
                }
            }

            $donorName = $namaRaw ?: 'Anonim / Hamba Dhamma';

            // Generate unique invoice number
            do {
                $invoiceNumber = 'DN-' . date('Ymd') . '-' . strtoupper(Str::random(6));
            } while (Donation::where('invoice_number', $invoiceNumber)->exists());

            Donation::create([
                'invoice_number' => $invoiceNumber,
                'donation_program_id' => $program->id,
                'donor_name' => $donorName,
                'amount' => $cleanAmount,
                'total_amount' => $cleanAmount,
                'payment_method' => 'transfer',
                'status' => 'verified',
            ]);

            $importedCount++;
        }

        return [
            'imported' => $importedCount,
            'created_programs' => $createdPrograms,
            'errors' => $errors,
        ];
    }

    /**
     * Extract tabular data from file (XLSX, CSV, or HTML-table XLS).
     */
    public function extractRows(string $filePath): array
    {
        if (!file_exists($filePath)) {
            return [];
        }

        // 1. Try XLSX via native ZipArchive
        if ($this->isZipFile($filePath)) {
            $xlsxRows = $this->parseXlsx($filePath);
            if (!empty($xlsxRows)) {
                return $xlsxRows;
            }
        }

        // 2. Try HTML table (often saved with .xls extension)
        $content = file_get_contents($filePath);
        if ($content && (stripos($content, '<table') !== false || stripos($content, '<tr') !== false)) {
            $htmlRows = $this->parseHtmlTable($content);
            if (!empty($htmlRows)) {
                return $htmlRows;
            }
        }

        // 3. Fallback to CSV with auto delimiter detection
        return $this->parseCsv($filePath);
    }

    private function isZipFile(string $filePath): bool
    {
        $handle = fopen($filePath, 'rb');
        if (!$handle) {
            return false;
        }
        $header = fread($handle, 4);
        fclose($handle);
        return $header === "PK\x03\x04";
    }

    private function parseXlsx(string $filePath): array
    {
        $zip = new ZipArchive();
        if ($zip->open($filePath) !== true) {
            return [];
        }

        // Read shared strings
        $strings = [];
        $sharedXmlContent = $zip->getFromName('xl/sharedStrings.xml');
        if ($sharedXmlContent) {
            $xml = @simplexml_load_string($sharedXmlContent);
            if ($xml && isset($xml->si)) {
                foreach ($xml->si as $si) {
                    if (isset($si->t)) {
                        $strings[] = (string) $si->t;
                    } elseif (isset($si->r)) {
                        $text = '';
                        foreach ($si->r as $r) {
                            $text .= (string) $r->t;
                        }
                        $strings[] = $text;
                    } else {
                        $strings[] = '';
                    }
                }
            }
        }

        // Find primary worksheet XML
        $sheetXmlContent = $zip->getFromName('xl/worksheets/sheet1.xml');
        if (!$sheetXmlContent) {
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $stat = $zip->statIndex($i);
                if (preg_match('#xl/worksheets/sheet\d+\.xml#i', $stat['name'])) {
                    $sheetXmlContent = $zip->getFromIndex($i);
                    break;
                }
            }
        }

        $rows = [];
        if ($sheetXmlContent) {
            $xml = @simplexml_load_string($sheetXmlContent);
            if ($xml && isset($xml->sheetData->row)) {
                foreach ($xml->sheetData->row as $row) {
                    $rowData = [];
                    foreach ($row->c as $cell) {
                        $cellRef = (string) $cell['r'];
                        $colLetters = preg_replace('/[0-9]/', '', $cellRef);
                        $colIdx = $this->colLetterToIndex($colLetters);

                        $type = (string) $cell['t'];
                        $val = (string) $cell->v;

                        if ($type === 's' && isset($strings[(int) $val])) {
                            $val = $strings[(int) $val];
                        } elseif ($type === 'inlineStr' && isset($cell->is->t)) {
                            $val = (string) $cell->is->t;
                        }

                        $rowData[$colIdx] = trim($val);
                    }

                    if (!empty($rowData)) {
                        $maxCol = max(array_keys($rowData));
                        $rowList = [];
                        for ($c = 0; $c <= $maxCol; $c++) {
                            $rowList[$c] = $rowData[$c] ?? '';
                        }
                        $rows[] = $rowList;
                    }
                }
            }
        }

        $zip->close();
        return $rows;
    }

    private function colLetterToIndex(string $letters): int
    {
        $letters = strtoupper(trim($letters));
        if ($letters === '') {
            return 0;
        }
        $index = 0;
        $len = strlen($letters);
        for ($i = 0; $i < $len; $i++) {
            $index = $index * 26 + (ord($letters[$i]) - ord('A') + 1);
        }
        return max(0, $index - 1);
    }

    private function parseCsv(string $filePath): array
    {
        $content = file_get_contents($filePath);
        if (!$content) {
            return [];
        }

        // Detect delimiter by inspecting the first line
        $firstLine = strtok($content, "\r\n") ?: '';
        $delimiters = [',', ';', "\t", '|'];
        $bestDelimiter = ',';
        $maxCount = 0;

        foreach ($delimiters as $delim) {
            $count = substr_count($firstLine, $delim);
            if ($count > $maxCount) {
                $maxCount = $count;
                $bestDelimiter = $delim;
            }
        }

        $rows = [];
        $handle = fopen($filePath, 'r');
        if ($handle) {
            while (($data = fgetcsv($handle, 0, $bestDelimiter)) !== false) {
                $rows[] = array_map(fn($v) => trim((string) $v), $data);
            }
            fclose($handle);
        }

        return $rows;
    }

    private function parseHtmlTable(string $html): array
    {
        $rows = [];
        if (preg_match_all('#<tr[^>]*>(.*?)</tr>#is', $html, $trMatches)) {
            foreach ($trMatches[1] as $tr) {
                if (preg_match_all('#<t[dh][^>]*>(.*?)</t[dh]>#is', $tr, $tdMatches)) {
                    $rows[] = array_map(fn($td) => trim(strip_tags(html_entity_decode($td))), $tdMatches[1]);
                }
            }
        }
        return $rows;
    }

    /**
     * Map columns to KegiatanID, Nama, and Jumlah indices.
     */
    private function determineColumnMapping(array $firstRow): array
    {
        $kegiatanIdx = null;
        $namaIdx = null;
        $jumlahIdx = null;
        $hasHeader = false;

        foreach ($firstRow as $idx => $headerText) {
            $clean = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', (string) $headerText));

            if ($kegiatanIdx === null && (str_contains($clean, 'kegiatan') || str_contains($clean, 'program'))) {
                $kegiatanIdx = $idx;
                $hasHeader = true;
            } elseif ($namaIdx === null && (str_contains($clean, 'nama') || str_contains($clean, 'donatur') || str_contains($clean, 'donor'))) {
                $namaIdx = $idx;
                $hasHeader = true;
            } elseif ($jumlahIdx === null && (str_contains($clean, 'jumlah') || str_contains($clean, 'nominal') || str_contains($clean, 'nilai') || str_contains($clean, 'amount') || str_contains($clean, 'total'))) {
                $jumlahIdx = $idx;
                $hasHeader = true;
            }
        }

        // Fallbacks if not detected by header text
        return [
            'is_header' => $hasHeader,
            'kegiatan' => $kegiatanIdx ?? 0,
            'nama' => $namaIdx ?? 1,
            'jumlah' => $jumlahIdx ?? 2,
        ];
    }
}
