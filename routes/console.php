<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('medicine:import-medex {path : Path to medex.db SQLite file}', function (string $path): int {
    if (! is_file($path)) {
        $this->error("SQLite file not found: {$path}");
        return 1;
    }

    $source = new PDO('sqlite:'.$path);
    $source->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $total = (int) $source->query('SELECT COUNT(*) FROM medicines')->fetchColumn();
    $this->info("Importing {$total} medicines...");

    $offset = 0;
    $limit = 500;
    $imported = 0;
    while ($offset < $total) {
        $rows = $source
            ->query("SELECT * FROM medicines ORDER BY id LIMIT {$limit} OFFSET {$offset}")
            ->fetchAll(PDO::FETCH_ASSOC);
        $payload = [];
        foreach ($rows as $row) {
            $unitPrice = preg_replace('/[^0-9.]/', '', (string) ($row['unit_price'] ?? ''));
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
                'sections' => $row['sections_json'] ?: null,
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
