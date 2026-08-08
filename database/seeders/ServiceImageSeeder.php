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
                $placeholder = $service->placeholderImagePath();
                $existingPlaceholder = $service->images
                    ->first(fn ($image) => str_starts_with($image->image_path, 'images/services/svg/') || preg_match('/^images\/services\/(haircuts-grooming|facial-cleanup|hair-colouring|oil-massage|hair-skin-treatments|combo-packages)(-thumb)?\.webp$/', $image->image_path));

                if ($existingPlaceholder) {
                    $existingPlaceholder->update([
                        'image_path' => $placeholder,
                        'thumbnail_path' => $placeholder,
                        'alt_text' => $service->name.' premium line art',
                        'is_cover' => true,
                    ]);

                    return;
                }

                if ($service->images->isNotEmpty()) {
                    return;
                }

                $service->images()->create([
                    'image_path' => $placeholder,
                    'thumbnail_path' => $placeholder,
                    'alt_text' => $service->name.' service image',
                    'is_cover' => true,
                    'sort_order' => 1,
                ]);
            });
    }
}
