<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
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

Artisan::command('medicine:import-assorted-csv {path : Path to Assorted Medicine Dataset medicine.csv} {--replace : Remove existing medicine catalog before importing}', function (string $path): int {
    if (! is_file($path)) {
        $this->error("Medicine CSV file not found: {$path}");
        return 1;
    }

    $handle = fopen($path, 'r');
    if (! $handle) {
        $this->error("Unable to open: {$path}");
        return 1;
    }

    $header = fgetcsv($handle);
    $normalize = fn ($value) => strtolower(trim((string) $value));
    $indexes = [];
    foreach ($header ?: [] as $index => $name) {
        $indexes[$normalize($name)] = $index;
    }

    $required = ['brand id', 'brand name', 'slug', 'dosage form', 'generic', 'strength', 'manufacturer', 'package container'];
    foreach ($required as $column) {
        if (! array_key_exists($column, $indexes)) {
            fclose($handle);
            $this->error("CSV missing required column: {$column}");
            return 1;
        }
    }

    $value = fn (array $row, string $column) => trim((string) ($row[$indexes[$column] ?? -1] ?? ''));
    $extractPrice = function (string $priceText): ?float {
        if (preg_match('/Unit Price:\s*৳\s*([0-9]+(?:\.[0-9]+)?)/u', $priceText, $match)) {
            return round((float) $match[1], 2);
        }

        if (preg_match('/৳\s*([0-9]+(?:\.[0-9]+)?)/u', $priceText, $match)) {
            return round((float) $match[1], 2);
        }

        return null;
    };

    if ($this->option('replace')) {
        $this->warn('Replacing existing medicine catalog...');
        DB::transaction(function (): void {
            DB::table('medicine_cart_items')->delete();
            DB::table('medicine_carts')->delete();
            if (Schema::hasTable('medicine_order_items') && Schema::hasColumn('medicine_order_items', 'medicine_item_id')) {
                DB::table('medicine_order_items')->update(['medicine_item_id' => null]);
            }
            DB::table('medicine_items')->delete();
        });
    }

    $total = max(0, count(file($path, FILE_SKIP_EMPTY_LINES)) - 1);
    $this->info("Importing {$total} medicines from Assorted Medicine Dataset CSV...");

    $payload = [];
    $imported = 0;
    while (($row = fgetcsv($handle)) !== false) {
        $sourceId = (int) $value($row, 'brand id');
        $brandName = $value($row, 'brand name');
        if ($sourceId <= 0 || $brandName === '') {
            continue;
        }

        $priceText = $value($row, 'package container');
        $payload[] = [
            'source_id' => $sourceId,
            'slug' => $value($row, 'slug') ?: Str::slug($brandName.'-'.$sourceId),
            'brand_name' => $brandName,
            'dosage_form' => $value($row, 'dosage form') ?: null,
            'strength' => $value($row, 'strength') ?: null,
            'generic_name' => $value($row, 'generic') ?: null,
            'generic_id' => null,
            'company' => $value($row, 'manufacturer') ?: null,
            'company_id' => null,
            'unit_price' => $extractPrice($priceText),
            'price_text' => $priceText ?: null,
            'pack_sizes' => $value($row, 'package size') ?: null,
            'indications' => null,
            'composition' => null,
            'pharmacology' => null,
            'dosage_and_administration' => null,
            'interaction' => null,
            'contraindications' => null,
            'side_effects' => null,
            'pregnancy_and_lactation' => null,
            'precautions_and_warnings' => null,
            'overdose_effects' => null,
            'therapeutic_class' => null,
            'storage_conditions' => null,
            'sections' => json_encode(['source' => 'assorted-medicine-dataset-of-bangladesh']),
            'is_available' => true,
            'updated_at' => now(),
            'created_at' => now(),
        ];

        if (count($payload) >= 500) {
            DB::table('medicine_items')->upsert(
                $payload,
                ['source_id'],
                array_values(array_diff(array_keys($payload[0]), ['source_id', 'created_at']))
            );
            $imported += count($payload);
            $this->line("Imported {$imported}/{$total}");
            $payload = [];
        }
    }

    if ($payload !== []) {
        DB::table('medicine_items')->upsert(
            $payload,
            ['source_id'],
            array_values(array_diff(array_keys($payload[0]), ['source_id', 'created_at']))
        );
        $imported += count($payload);
        $this->line("Imported {$imported}/{$total}");
    }

    fclose($handle);
    $this->info('Assorted medicine import complete.');
    return 0;
})->purpose('Import medicine catalog with package/unit prices from Assorted Medicine Dataset of Bangladesh medicine.csv');

Artisan::command('medicine:import-assorted-generics {path : Path to Assorted Medicine Dataset generic.csv}', function (string $path): int {
    if (! is_file($path)) {
        $this->error("Generic CSV file not found: {$path}");
        return 1;
    }

    $handle = fopen($path, 'r');
    if (! $handle) {
        $this->error("Unable to open: {$path}");
        return 1;
    }

    $header = fgetcsv($handle);
    $normalize = fn ($value) => strtolower(trim((string) $value));
    $indexes = [];
    foreach ($header ?: [] as $index => $name) {
        $indexes[$normalize($name)] = $index;
    }

    foreach (['generic id', 'generic name'] as $column) {
        if (! array_key_exists($column, $indexes)) {
            fclose($handle);
            $this->error("CSV missing required column: {$column}");
            return 1;
        }
    }

    $value = fn (array $row, string $column) => trim((string) ($row[$indexes[$column] ?? -1] ?? ''));
    $description = fn (array $row, string $column) => $value($row, $column) ?: null;
    $combine = function (?string ...$parts): ?string {
        $filtered = array_values(array_filter($parts, fn ($part) => trim((string) $part) !== ''));
        return $filtered === [] ? null : implode("\n\n", $filtered);
    };

    $updatedGenerics = 0;
    while (($row = fgetcsv($handle)) !== false) {
        $genericName = $value($row, 'generic name');
        if ($genericName === '') {
            continue;
        }

        $sections = [
            'source' => 'assorted-medicine-dataset-of-bangladesh',
            'generic_slug' => $description($row, 'slug'),
            'monograph_link' => $description($row, 'monograph link'),
        ];

        $updated = DB::table('medicine_items')
            ->where('generic_name', $genericName)
            ->update([
                'generic_id' => (int) $value($row, 'generic id') ?: null,
                'indications' => $description($row, 'indication description') ?: $description($row, 'indication'),
                'pharmacology' => $description($row, 'pharmacology description'),
                'dosage_and_administration' => $combine(
                    $description($row, 'dosage description'),
                    $description($row, 'administration description'),
                    $description($row, 'pediatric usage description'),
                    $description($row, 'duration of treatment description'),
                    $description($row, 'reconstitution description'),
                ),
                'interaction' => $description($row, 'interaction description'),
                'contraindications' => $description($row, 'contraindications description'),
                'side_effects' => $description($row, 'side effects description'),
                'pregnancy_and_lactation' => $description($row, 'pregnancy and lactation description'),
                'precautions_and_warnings' => $description($row, 'precautions description'),
                'overdose_effects' => $description($row, 'overdose effects description'),
                'therapeutic_class' => $description($row, 'drug class'),
                'storage_conditions' => $description($row, 'storage conditions description'),
                'sections' => json_encode($sections),
                'updated_at' => now(),
            ]);

        if ($updated > 0) {
            $updatedGenerics++;
        }
    }

    fclose($handle);
    $this->info("Enriched medicines from {$updatedGenerics} generic rows.");
    return 0;
})->purpose('Enrich imported medicines with generic monograph details from Assorted Medicine Dataset generic.csv');

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
