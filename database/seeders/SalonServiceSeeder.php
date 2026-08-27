<?php

namespace Database\Seeders;

use App\Models\Service;
use App\Models\ServiceCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class SalonServiceSeeder extends Seeder
{
    public function run(): void
    {
        $categories = $this->seedCategories();
        $slugs = collect();
        $order = 10;

        foreach ($this->catalog() as $categorySlug => $services) {
            foreach ($services as $row) {
                $slugs->push($this->upsertService($categories[$categorySlug], $row, $order));
                $order += 10;
            }
        }

        Service::query()
            ->whereNotIn('slug', $slugs->all())
            ->whereNull('deleted_at')
            ->delete();

        $this->command?->info('Final salon catalog seeded: '.$slugs->count().' services/packages active.');
    }

    private function seedCategories(): array
    {
        $rows = [
            ['Hair Cuts', 'hair-cuts', 'Hair wash, shave, beard and haircut essentials.', 10, 'images/services/haircuts-grooming.webp'],
            ['Combos', 'combos', 'Value grooming combinations for faster billing and booking.', 20, 'images/services/combo-packages.webp'],
            ['Hair Colouring', 'hair-colouring', 'Hair colour, black coverage and highlight services.', 30, 'images/services/hair-colouring.webp'],
            ['Oil Massage', 'oil-massage', 'Relaxing oil massage and de-tan services.', 40, 'images/services/oil-massage.webp'],
            ['Facial with Bleach', 'facial-with-bleach', 'Cleanup, facial and premium skin-care services.', 50, 'images/services/facial-cleanup.webp'],
            ['Bleach', 'bleach', 'Bleach and de-tan bleach services.', 60, 'images/services/hair-skin-treatments.webp'],
            ['Others', 'others', 'Other salon services and custom highlight pricing.', 70, 'images/services/hair-skin-treatments.webp'],
        ];

        $categories = [];

        foreach ($rows as [$name, $slug, $description, $order, $image]) {
            $categories[$slug] = ServiceCategory::query()->updateOrCreate(
                ['slug' => $slug],
                [
                    'name' => $name,
                    'description' => $description,
                    'display_order' => $order,
                    'image' => $image,
                    'is_active' => true,
                ],
            );
        }

        ServiceCategory::query()
            ->whereNotIn('slug', array_keys($categories))
            ->update(['is_active' => false]);

        return $categories;
    }

    private function catalog(): array
    {
        return [
            'hair-cuts' => [
                ['name' => 'Hair Wash', 'price' => 60],
                ['name' => 'Shave', 'price' => 80],
                ['name' => 'Beard Trim', 'price' => 80],
                ['name' => 'Kids Cut', 'price' => 110],
                ['name' => 'Hair Cut', 'price' => 120, 'featured' => true],
                ['name' => 'Model Cut', 'price' => 130],
                ['name' => 'Hair Cut & Shaving', 'price' => 180, 'featured' => true],
                ['name' => 'Head Shave', 'price' => 200],
            ],
            'combos' => [
                ['name' => 'Hair Cut + Clean-Up + Face Clean + Beard Setting', 'price' => 249, 'package' => true],
                ['name' => 'Hair Cut + Coconut Oil Massage + Beard Setting', 'price' => 299, 'package' => true],
                ['name' => 'Hair Cut + Fruit Bleach + Beard Setting', 'price' => 299, 'package' => true],
                ['name' => 'Hair Cut + Beard Setting + Fruit Bleach + Fruit Facial', 'price' => 449, 'package' => true],
                ['name' => 'Hair Cut + Beard Setting + Hair Spa', 'price' => 449, 'package' => true],
                ['name' => 'Hair Cut + Beard Setting + De-Tan', 'price' => 449, 'package' => true],
                ['name' => 'Hair Cut + Coconut Oil Massage + Beard Setting + Bleach', 'price' => 499, 'package' => true],
                ['name' => 'Hair Cut + Facial', 'price' => 549, 'package' => true],
                ['name' => 'Hair Cut + Hair Spa + Facial', 'price' => 749, 'package' => true],
                ["name" => "Hair Cut + L'Oréal Hair Spa + Hair Wash", 'price' => 899, 'package' => true],
                ['name' => 'Hair Cut + Hair Dye + Oil Massage + Hair Wash', 'price' => 999, 'package' => true],
            ],
            'hair-colouring' => [
                ['name' => 'Black Henna', 'price' => 200],
                ['name' => 'Fruit Vinegar Colour', 'price' => 350],
                ['name' => 'Garnier Black', 'price' => 350],
                ["name" => "L'Oréal Black", 'price' => 700],
                ['name' => 'Fashion Colouring - Gold, Red, Maroon & Blue', 'price' => 800],
            ],
            'oil-massage' => [
                ['name' => 'Coconut Oil Massage', 'price' => 150],
                ['name' => 'Navratna Oil Massage', 'price' => 300],
                ['name' => 'Amla Oil Massage', 'price' => 300],
                ['name' => 'Gingelly Oil Massage', 'price' => 300],
                ['name' => 'Olive Oil Massage', 'price' => 300],
                ['name' => 'Almond Oil Massage', 'price' => 300],
                ['name' => 'De-Tan', 'price' => 350],
            ],
            'facial-with-bleach' => [
                ['name' => 'Clean-Up', 'price' => 150],
                ['name' => 'Fruit Facial', 'price' => 500],
                ['name' => 'Wine Facial', 'price' => 500],
                ['name' => 'Banana Facial', 'price' => 500],
                ['name' => 'Papaya Facial', 'price' => 500],
                ['name' => 'Pearl Facial', 'price' => 500],
                ['name' => 'Skin Whitening Facial', 'price' => 500],
                ['name' => '24K Gold Facial', 'price' => 1000],
                ["name" => "Nature's Gold Facial", 'price' => 1000],
                ['name' => 'Diamond Facial', 'price' => 1100],
                ['name' => 'Strawberry Facial', 'price' => 1200],
                ['name' => 'Raaga Skin Lightening Facial', 'price' => 1200],
            ],
            'bleach' => [
                ['name' => 'Lactogen Bleach', 'price' => 200],
                ['name' => 'De-Tan Bleach', 'price' => 350],
            ],
            'others' => [
                ['name' => 'Hair Dandruff Removal', 'price' => 449],
                ['name' => 'Ear Piercing', 'price' => 50],
                ['name' => 'Hair Colour Highlight', 'price' => null, 'price_type' => 'range', 'minimum' => 250, 'maximum' => 400],
            ],
        ];
    }

    private function upsertService(ServiceCategory $category, array $row, int $order): string
    {
        $name = $row['name'];
        $priceType = $row['price_type'] ?? 'fixed';
        $isPackage = (bool) ($row['package'] ?? false);
        $slug = Str::slug($name);
        $description = $this->descriptionFor($name, $category->name, $isPackage);

        Service::withTrashed()->updateOrCreate(
            ['slug' => $slug],
            [
                'category_id' => $category->id,
                'name' => $name,
                'service_code' => ($isPackage ? 'PKG-' : 'SVC-').Str::upper(Str::slug($name, '-')),
                'short_description' => $description,
                'detailed_description' => $description,
                'price_type' => $priceType,
                'price' => $priceType === 'fixed' ? $row['price'] : null,
                'minimum_price' => $row['minimum'] ?? null,
                'maximum_price' => $row['maximum'] ?? null,
                'price_on_request' => false,
                'currency_code' => 'INR',
                'is_package' => $isPackage,
                'is_salon_service_available' => true,
                'duration_minutes' => $this->durationFor($name, $isPackage),
                'image' => $this->imageFor($name, $category->slug, $isPackage),
                'gallery_images' => null,
                'is_featured' => (bool) ($row['featured'] ?? false),
                'is_home_service_available' => false,
                'home_service_price' => null,
                'home_service_visit_charge' => null,
                'pricing_note' => $priceType === 'range' ? 'Custom billing price must be between Rs. 250 and Rs. 400.' : null,
                'included_services' => $isPackage ? array_map('trim', explode('+', $name)) : null,
                'regular_total' => null,
                'savings_amount' => null,
                'gender_applicability' => 'men',
                'status' => 'active',
                'display_order' => $order,
                'deleted_at' => null,
            ],
        );

        return $slug;
    }

    private function descriptionFor(string $name, string $category, bool $isPackage): string
    {
        return $isPackage
            ? 'Value combo package for '.$name.'.'
            : $name.' service from the '.$category.' catalog.';
    }

    private function durationFor(string $name, bool $isPackage): int
    {
        if ($isPackage) {
            return str_contains($name, '999') ? 150 : 90;
        }

        $text = Str::lower($name);

        return match (true) {
            str_contains($text, 'facial'), str_contains($text, 'colour'), str_contains($text, 'henna'), str_contains($text, 'dandruff') => 60,
            str_contains($text, 'massage'), str_contains($text, 'bleach'), str_contains($text, 'tan') => 30,
            str_contains($text, 'piercing') => 15,
            default => 25,
        };
    }

    private function imageFor(string $name, string $categorySlug, bool $isPackage): string
    {
        return 'images/services/generated/'.Str::slug($name).'.webp';
    }
}
