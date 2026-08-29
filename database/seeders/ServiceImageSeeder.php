<?php

namespace Database\Seeders;

use App\Models\Service;
use Illuminate\Database\Seeder;

class ServiceImageSeeder extends Seeder
{
    public function run(): void
    {
        Service::query()
            ->with('images')
            ->orderBy('id')
            ->each(function (Service $service): void {
                $coverPath = $service->placeholderImagePath();
                $existingPlaceholder = $service->images->first();

                if ($existingPlaceholder) {
                    $existingPlaceholder->update([
                        'image_path' => $coverPath,
                        'thumbnail_path' => $coverPath,
                        'alt_text' => 'Premium salon photography for '.$service->name,
                        'is_cover' => true,
                    ]);

                    return;
                }

                if ($service->images->isNotEmpty()) {
                    return;
                }

                $service->images()->create([
                    'image_path' => $coverPath,
                    'thumbnail_path' => $coverPath,
                    'alt_text' => 'Premium salon photography for '.$service->name,
                    'is_cover' => true,
                    'sort_order' => 1,
                ]);
            });
    }
}
