<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('medicine:import-medex {path : Path to medex.db SQLite/CSV file}', function (string $path): int {
    if (! is_file($path)) {
        $this->error("Medicine source file not found: {$path}");
        return 1;
    }

    $columns = [
        'id', 'slug', 'brand_name', 'dosage_form', 'strength', 'generic_name', 'generic_id', 'company', 'company_id',
        'unit_price', 'price_text', 'pack_sizes', 'indications', 'composition', 'pharmacology', 'dosage_and_administration',
        'interaction', 'contraindications', 'side_effects', 'pregnancy_and_lactation', 'precautions_and_warnings',
        'overdose_effects', 'therapeutic_class', 'storage_conditions', 'sections_json',
    ];

    $readChunk = function (int $limit, int $offset) use ($path, $columns) {
        if (str_ends_with(strtolower($path), '.csv')) {
            $handle = fopen($path, 'r');
            if (! $handle) {
                return [];
            }
            $rows = [];
            $line = 0;
            while (($data = fgetcsv($handle)) !== false) {
                if ($line++ < $offset) {
                    continue;
                }
                if (count($rows) >= $limit) {
                    break;
                }
                $rows[] = array_combine($columns, array_slice(array_pad($data, count($columns), null), 0, count($columns)));
            }
            fclose($handle);
            return $rows;
        }

        $source = new PDO('sqlite:'.$path);
        $source->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $source
            ->query("SELECT * FROM medicines ORDER BY id LIMIT {$limit} OFFSET {$offset}")
            ->fetchAll(PDO::FETCH_ASSOC);
    };

    $total = str_ends_with(strtolower($path), '.csv')
        ? max(0, count(file($path, FILE_SKIP_EMPTY_LINES)))
        : (int) (new PDO('sqlite:'.$path))->query('SELECT COUNT(*) FROM medicines')->fetchColumn();
    $this->info("Importing {$total} medicines...");

    $offset = 0;
    $limit = 500;
    $imported = 0;
    while ($offset < $total) {
        $rows = $readChunk($limit, $offset);
        $payload = [];
        foreach ($rows as $row) {
            $unitPrice = preg_replace('/[^0-9.]/', '', (string) ($row['unit_price'] ?? ''));
            $sectionsJson = $row['sections_json'] ?: null;
            if ($sectionsJson !== null && json_decode($sectionsJson, true) === null && json_last_error() !== JSON_ERROR_NONE) {
                $sectionsJson = null;
            }
            $payload[] = [
                'source_id' => $row['id'],
                'slug' => $row['slug'] ?: Str::slug(($row['brand_name'] ?? 'medicine').'-'.$row['id']),
                'brand_name' => $row['brand_name'] ?: 'Medicine',
                'dosage_form' => $row['dosage_form'] ?: null,
                'strength' => $row['strength'] ?: null,
                'generic_name' => $row['generic_name'] ?: null,
                'generic_id' => $row['generic_id'] ?: null,
                'company' => $row['company'] ?: null,
                'company_id' => $row['company_id'] ?: null,
                'unit_price' => $unitPrice === '' ? null : round((float) $unitPrice, 2),
                'price_text' => $row['price_text'] ?: null,
                'pack_sizes' => $row['pack_sizes'] ?: null,
                'indications' => $row['indications'] ?: null,
                'composition' => $row['composition'] ?: null,
                'pharmacology' => $row['pharmacology'] ?: null,
                'dosage_and_administration' => $row['dosage_and_administration'] ?: null,
                'interaction' => $row['interaction'] ?: null,
                'contraindications' => $row['contraindications'] ?: null,
                'side_effects' => $row['side_effects'] ?: null,
                'pregnancy_and_lactation' => $row['pregnancy_and_lactation'] ?: null,
                'precautions_and_warnings' => $row['precautions_and_warnings'] ?: null,
                'overdose_effects' => $row['overdose_effects'] ?: null,
                'therapeutic_class' => $row['therapeutic_class'] ?: null,
                'storage_conditions' => $row['storage_conditions'] ?: null,
                'sections' => $sectionsJson,
                'is_available' => true,
                'updated_at' => now(),
                'created_at' => now(),
            ];
        }

        DB::table('medicine_items')->upsert(
            $payload,
            ['source_id'],
            array_values(array_diff(array_keys($payload[0] ?? []), ['source_id', 'created_at']))
        );

        $imported += count($payload);
        $offset += $limit;
        $this->line("Imported {$imported}/{$total}");
    }

    $this->info('Medicine import complete.');
    return 0;
})->purpose('Import Bangladeshi medicine data from NasirSunny50/Bangladeshi-Medicine-API medex.db');

Artisan::command('medicine:import-images {path : CSV with source_id,image_url}', function (string $path): int {
    if (! is_file($path)) {
        $this->error("Image CSV file not found: {$path}");
        return 1;
    }

    $handle = fopen($path, 'r');
    if (! $handle) {
        $this->error("Unable to open: {$path}");
        return 1;
    }

    $header = fgetcsv($handle);
    $sourceIndex = array_search('source_id', $header ?: [], true);
    $imageIndex = array_search('image_url', $header ?: [], true);
    if ($sourceIndex === false || $imageIndex === false) {
        fclose($handle);
        $this->error('CSV must include source_id and image_url headers.');
        return 1;
    }

    $updated = 0;
    while (($row = fgetcsv($handle)) !== false) {
        $sourceId = (int) ($row[$sourceIndex] ?? 0);
        $imageUrl = trim((string) ($row[$imageIndex] ?? ''));
        if ($sourceId <= 0 || $imageUrl === '') {
            continue;
        }
        $updated += DB::table('medicine_items')
            ->where('source_id', $sourceId)
            ->update(['image_url' => $imageUrl, 'updated_at' => now()]);
    }
    fclose($handle);

    $this->info("Imported {$updated} medicine images.");
    return 0;
})->purpose('Import MedEx medicine pack image URLs by source id');

Artisan::command('medicine:fill-dosage-images', function (): int {
    $map = [
        'tablet' => 'tablet.png',
        'capsule' => 'capsule.png',
        'syrup' => 'syrup.png',
        'suspension' => 'suspension.png',
        'oral suspension' => 'suspension.png',
        'injection' => 'injection.png',
        'iv injection' => 'injection.png',
        'im/iv injection' => 'injection.png',
        'cream' => 'cream.png',
        'ointment' => 'ointment.png',
        'gel' => 'gel.png',
        'drop' => 'drop.png',
        'eye drop' => 'eye-drop.png',
        'oral drop' => 'drop.png',
        'powder' => 'powder.png',
        'oral powder' => 'powder.png',
        'suppository' => 'suppository.png',
        'inhaler' => 'inhaler.png',
        'spray' => 'spray.png',
        'solution' => 'solution.png',
    ];

    $updated = 0;
    foreach ($map as $form => $file) {
        $updated += DB::table('medicine_items')
            ->whereNull('image_url')
            ->whereRaw('LOWER(dosage_form) = ?', [$form])
            ->update([
                'image_url' => 'https://medex.com.bd/img/dosage-forms/'.$file,
                'updated_at' => now(),
            ]);
    }

    $this->info("Filled {$updated} dosage fallback images.");
    return 0;
})->purpose('Fill missing medicine images with MedEx dosage form icons');
