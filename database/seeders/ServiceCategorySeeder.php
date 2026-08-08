<?php

namespace Database\Seeders;

use App\Models\ServiceCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ServiceCategorySeeder extends Seeder
{
    public function run(): void
    {
        collect([
            ['Haircuts & Grooming', 'haircuts-grooming', 'Professional haircut, shave, beard and grooming essentials for men.'],
            ['Facial & Cleanup', 'facial-cleanup', 'Facial and cleanup services designed to refresh the appearance of the skin.'],
            ['Hair Colouring', 'hair-colouring', 'Hair colour and highlighting services with consultation-led finish and coverage.'],
            ['Oil Massage', 'oil-massage', 'Relaxing head oil massage options focused on comfort and grooming.'],
            ['Hair & Skin Treatments', 'hair-skin-treatments', 'Care-focused hair and skin services with final selection guided by staff.'],
            ['SMART SAVER PACKAGES', 'combo-packages', 'Enjoy more services together and save more with carefully curated grooming packages.'],
        ])->each(function (array $row): void {
            [$name, $slug, $description] = $row;
            ServiceCategory::query()->updateOrCreate(
                ['slug' => $slug],
                ['name' => $name, 'description' => $description, 'is_active' => true]
            );
        });
    }
}
