<?php

use App\Services\CustomerImportService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('salon:import-customers {--path= : Text file extracted from the customer PDF} {--dry-run : Parse and validate without writing}', function (): int {
    $importer = app(CustomerImportService::class);
    $path = $this->option('path') ?: env('CUSTOMER_IMPORT_PATH', storage_path('app/imports/customer-list.txt'));
    $result = $importer->import($path, (bool) $this->option('dry-run'));

    $this->info('Customer import summary');
    $this->line('Source: '.$path);
    $this->table(['Metric', 'Count'], [
        ['Parsed', $result['total']],
        ['Imported', $result['imported']],
        ['Skipped', $result['skipped']],
        ['Duplicates', $result['duplicates']],
        ['Errors', $result['errors']],
    ]);

    foreach (array_slice($result['messages'], 0, 25) as $message) {
        $this->warn($message);
    }

    if (count($result['messages']) > 25) {
        $this->warn('Additional messages omitted: '.(count($result['messages']) - 25));
    }

    return $result['errors'] > 0 ? 1 : 0;
})->purpose('Safely import historical customers from the salon customer list export');
