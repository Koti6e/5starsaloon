<?php

use App\Services\CustomerImportService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use App\Services\GoogleSearchConsoleService;
use Illuminate\Support\Carbon;

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

Artisan::command('seo:gsc-submit-sitemap', function (): int {
    try {
        app(GoogleSearchConsoleService::class)->submitSitemap();
        $this->info('Submitted '.rtrim(config('app.url'), '/').'/sitemap.xml to Google Search Console.');
        return 0;
    } catch (\Throwable $exception) {
        $this->error($exception->getMessage());
        return 1;
    }
})->purpose('Submit the public XML sitemap to an authorized Search Console property');

Artisan::command('seo:gsc-performance {--days=28}', function (): int {
    try {
        $end = Carbon::now('Asia/Kolkata')->subDays(3);
        $start = $end->copy()->subDays(max(1, (int) $this->option('days')) - 1);
        $rows = app(GoogleSearchConsoleService::class)->performance($start->toDateString(), $end->toDateString());
        $this->table(['Clicks', 'Impressions', 'CTR', 'Avg Position', 'Date / Query / Page'], array_map(fn ($row) => [
            $row['clicks'] ?? 0, $row['impressions'] ?? 0, round(($row['ctr'] ?? 0) * 100, 2).'%', round($row['position'] ?? 0, 1), implode(' · ', $row['keys'] ?? []),
        ], array_slice($rows, 0, 50)));
        return 0;
    } catch (\Throwable $exception) {
        $this->error($exception->getMessage());
        return 1;
    }
})->purpose('Display Search Console query, page, click and impression data');

Artisan::command('seo:gsc-inspect {url}', function (string $url): int {
    try {
        $result = app(GoogleSearchConsoleService::class)->inspect($url);
        $this->line(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        return 0;
    } catch (\Throwable $exception) {
        $this->error($exception->getMessage());
        return 1;
    }
})->purpose('Inspect Google index status for a URL in the authorized Search Console property');
