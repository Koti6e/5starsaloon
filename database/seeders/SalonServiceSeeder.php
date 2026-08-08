<?php

namespace Database\Seeders;

use App\Models\Service;
use App\Models\ServiceCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class SalonServiceSeeder extends Seeder
{
    private int $contactCount = 0;
    private int $startingCount = 0;
    private int $rangeCount = 0;
    private int $packageCount = 0;

    public function run(): void
    {
        $categories = $this->seedCategories();
        $services = $this->seedIndividualServices($categories);
        $this->seedPackages($categories['combo-packages'], $services);
        $this->call(ServiceImageSeeder::class);

        $this->command?->info('Salon menu seeded:');
        $this->command?->line('Categories created or updated: '.count($categories));
        $this->command?->line('Individual services created or updated: '.count($services));
        $this->command?->line('Packages created or updated: '.$this->packageCount);
        $this->command?->line('Contact-for-price services: '.$this->contactCount);
        $this->command?->line('Starting-from services: '.$this->startingCount);
        $this->command?->line('Range-price services: '.$this->rangeCount);
    }

    /**
     * @return array<string, ServiceCategory>
     */
    private function seedCategories(): array
    {
        $rows = [
            ['Haircuts & Grooming', 'haircuts-grooming', 'Professional haircut, shave, beard and grooming essentials for men.', 10, 'images/services/haircuts-grooming.webp'],
            ['Facial & Cleanup', 'facial-cleanup', 'Facial and cleanup services designed to refresh the appearance of the skin.', 20, 'images/services/facial-cleanup.webp'],
            ['Hair Colouring', 'hair-colouring', 'Hair colour and highlighting services with consultation-led finish and coverage.', 30, 'images/services/hair-colouring.webp'],
            ['Oil Massage', 'oil-massage', 'Relaxing head oil massage options focused on comfort and grooming.', 40, 'images/services/oil-massage.webp'],
            ['Hair & Skin Treatments', 'hair-skin-treatments', 'Care-focused hair and skin services with final selection guided by staff.', 50, 'images/services/hair-skin-treatments.webp'],
            ['SMART SAVER PACKAGES', 'combo-packages', 'Enjoy more services together and save more with carefully curated grooming packages.', 60, 'images/services/svg/package.svg'],
        ];

        $categories = [];

        foreach ($rows as [$name, $slug, $description, $order, $image]) {
            $category = ServiceCategory::query()->updateOrCreate(
                ['slug' => $slug],
                [
                    'name' => $name,
                    'description' => $description,
                    'display_order' => $order,
                    'image' => $image,
                    'is_active' => true,
                ]
            );

            $categories[$category->slug] = $category;
        }

        return $categories;
    }

    /**
     * @param  array<string, ServiceCategory>  $categories
     * @return array<string, Service>
     */
    private function seedIndividualServices(array $categories): array
    {
        $rows = [
            ['Haircuts & Grooming', 'Hair Wash', 60, 15, 'A clean hair wash service to refresh the scalp and prepare hair for styling.'],
            ['Haircuts & Grooming', 'Shave', 80, 20, 'A neat shaving service finished with careful grooming attention.'],
            ['Haircuts & Grooming', 'Beard Trim', 80, 20, 'Beard shaping and trimming for a clean, maintained look.'],
            ['Haircuts & Grooming', 'Kids Haircut', 110, 30, 'A simple, comfortable haircut service for children.'],
            ['Haircuts & Grooming', 'Haircut', 120, 30, 'A professional haircut shaped around the customer’s preferred style.'],
            ['Haircuts & Grooming', 'Head Shave', 200, 30, 'A complete head shave service carried out with care and hygiene.'],
            ['Haircuts & Grooming', 'Model Haircut', 130, 40, 'A styled haircut option for customers who want a more defined finish.'],
            ['Haircuts & Grooming', 'Haircut & Shaving', 180, 50, 'A combined haircut and shave service for a complete grooming visit.'],

            ['Facial & Cleanup', 'Cleanup', 150, 30, 'A focused cleanup designed to refresh the appearance of the skin.'],
            ['Facial & Cleanup', 'Fruit Facial', 500, 45, 'A facial service designed to provide a cleaner, brighter-looking finish.'],
            ['Facial & Cleanup', 'Wine Facial', 500, 45, 'A facial service focused on refreshed-looking skin and a polished finish.'],
            ['Facial & Cleanup', 'Banana Facial', 500, 45, 'A gentle facial option designed for a clean and refreshed appearance.'],
            ['Facial & Cleanup', 'Papaya Facial', 500, 45, 'A facial service designed to refresh the appearance of the skin.'],
            ['Facial & Cleanup', 'Pearl Facial', 500, 45, 'A facial service intended to support a cleaner, brighter-looking finish.'],
            ['Facial & Cleanup', 'Skin Brightening Facial', 500, 45, 'Designed to refresh the appearance of the skin and provide a cleaner, brighter-looking finish.'],
            ['Facial & Cleanup', '24 Carat Gold Facial', 1000, 60, 'A premium facial service for a refreshed and well-groomed appearance.'],
            ['Facial & Cleanup', 'Nature’s Gold Facial', 1000, 60, 'A gold facial option designed for a refreshed-looking finish.'],
            ['Facial & Cleanup', 'Diamond Facial', 1100, 60, 'A premium facial service focused on clean, polished-looking skin.'],
            ['Facial & Cleanup', 'Strawberry Facial', 1200, 60, 'A facial service designed to refresh the appearance of the skin.'],
            ['Facial & Cleanup', 'Raaga Skin Facial', 1200, 60, 'A facial service designed for refreshed-looking skin and personal care.'],
            ['Facial & Cleanup', 'Lightening Facial', null, 60, 'A facial care service with final pricing confirmed by salon staff.', 'contact'],
            ['Facial & Cleanup', 'Lactogen Bleach', null, 30, 'A bleach service with final pricing confirmed by salon staff.', 'contact'],
            ['Facial & Cleanup', 'De-Tan Bleach', null, 30, 'A de-tan bleach service with final pricing confirmed by salon staff.', 'contact'],

            ['Hair Colouring', 'Black Henna', 200, 45, 'Black henna application for hair colour coverage.'],
            ['Hair Colouring', 'Fruit Vinegar Hair Colour', 350, 45, 'Hair colour service using the listed fruit vinegar colour option.'],
            ['Hair Colouring', 'Garnier Black', 350, 45, 'Black hair colour application using the listed Garnier option.'],
            ['Hair Colouring', 'L’Oréal Black', 700, 50, 'Black hair colour application using the listed L’Oréal option.'],
            ['Hair Colouring', 'Fashion Colouring', 800, 90, 'Fashion colouring available in gold, red, maroon and blue shades.'],
            ['Hair Colouring', 'Hair Colour Highlight', null, 60, 'Highlight pricing may vary depending on colour selection, hair length and required coverage.', 'range', 250, 400],

            ['Oil Massage', 'Coconut Oil Massage', 150, 30, 'A relaxing head oil massage using coconut oil.'],
            ['Oil Massage', 'Navaratna Oil Massage', 300, 30, 'A relaxing head oil massage using Navaratna oil.'],
            ['Oil Massage', 'Amla Oil Massage', 300, 30, 'A relaxing head oil massage using amla oil.'],
            ['Oil Massage', 'Gingelly Oil Massage', 300, 30, 'A relaxing head oil massage using gingelly oil.'],
            ['Oil Massage', 'Olive Oil Massage', 300, 30, 'A relaxing head oil massage using olive oil.'],
            ['Oil Massage', 'Almond Oil Massage', 300, 30, 'A relaxing head oil massage using almond oil.'],
            ['Oil Massage', 'De-Tan Treatment', 350, 30, 'A de-tan care service for a refreshed grooming finish.'],

            ['Hair & Skin Treatments', 'Dandruff Care', 449, 60, 'Service selection and final amount depend on the customer’s hair condition and the treatment recommended by salon staff.', 'starting_from', 449],
            ['Hair & Skin Treatments', 'Hair Growth Care', null, 60, 'A hair care consultation service with final pricing confirmed by salon staff.', 'contact'],
            ['Hair & Skin Treatments', 'Skin Cleanup / Pimple Care', null, 45, 'A skin cleanup service with final pricing confirmed by salon staff.', 'contact'],
            ['Hair & Skin Treatments', 'Tattoo Service', null, null, 'Tattoo service details and pricing are confirmed by salon staff.', 'contact'],
            ['Hair & Skin Treatments', 'Ear Piercing', 50, 15, 'Ear piercing service with simple aftercare guidance from staff.'],
        ];

        $services = [];
        $displayOrder = 10;

        foreach ($rows as $row) {
            [$categoryName, $name, $price, $duration, $description] = $row;
            $priceType = $row[5] ?? 'fixed';
            $minimumPrice = $row[6] ?? ($priceType === 'starting_from' ? $price : null);
            $maximumPrice = $row[7] ?? null;
            $category = $categories[Str::slug($categoryName)];
            $slug = Str::slug($name);

            $service = Service::query()->updateOrCreate(
                ['slug' => $slug],
                [
                    'category_id' => $category->id,
                    'name' => $name,
                    'service_code' => 'SVC-'.Str::upper(Str::slug($name, '-')),
                    'short_description' => $description,
                    'detailed_description' => $description,
                    'price_type' => $priceType,
                    'price' => $priceType === 'fixed' ? $price : ($priceType === 'starting_from' ? $price : null),
                    'minimum_price' => $minimumPrice,
                    'maximum_price' => $maximumPrice,
                    'price_on_request' => $priceType === 'contact',
                    'currency_code' => 'INR',
                    'is_package' => false,
                    'is_salon_service_available' => true,
                    'duration_minutes' => $duration,
                    'image' => $this->placeholderFor($name, $categoryName, false),
                    'is_featured' => in_array($name, ['Haircut', 'Beard Trim', '24 Carat Gold Facial', 'L’Oréal Black', 'Coconut Oil Massage', 'Dandruff Care'], true),
                    'is_home_service_available' => false,
                    'pricing_note' => $this->pricingNote($priceType),
                    'gender_applicability' => 'men',
                    'status' => 'active',
                    'display_order' => $displayOrder,
                ]
            );

            if ($priceType === 'contact') {
                $this->contactCount++;
            } elseif ($priceType === 'starting_from') {
                $this->startingCount++;
            } elseif ($priceType === 'range') {
                $this->rangeCount++;
            }

            $services[$slug] = $service;
            $displayOrder += 10;
        }

        return $services;
    }

    /**
     * @param  array<string, Service>  $services
     */
    private function seedPackages(ServiceCategory $category, array $services): void
    {
        $packages = [
            ['Haircut + Beard Setting + De-Tan', 449, 90, ['Haircut', 'Beard Setting', 'De-Tan']],
            ['Haircut + Beard Setting + Cleanup + Face Clean', 249, 75, ['Haircut', 'Beard Setting', 'Cleanup', 'Face Clean']],
            ['Haircut + L’Oréal Spa + Hair Wash', 899, 120, ['Haircut', 'L’Oréal Spa', 'Hair Wash']],
            ['Haircut + Facial', 549, 90, ['Haircut', 'Facial']],
            ['Haircut + Hair Dye + Oil Massage + Hair Wash', 999, 150, ['Haircut', 'Hair Dye', 'Oil Massage', 'Hair Wash']],
            ['Haircut + Hair Spa + Facial', 749, 150, ['Haircut', 'Hair Spa', 'Facial']],
            ['Haircut + Coconut Oil Massage + Beard Setting', 299, 90, ['Haircut', 'Coconut Oil Massage', 'Beard Setting']],
            ['Haircut + Fruit Bleach + Beard Setting', 299, 90, ['Haircut', 'Fruit Bleach', 'Beard Setting']],
            ['Haircut + Beard Setting + Coconut Oil Massage + Bleach', 499, 120, ['Haircut', 'Beard Setting', 'Coconut Oil Massage', 'Bleach']],
            ['Haircut + Fruit Bleach + Fruit Facial + Beard Setting', 449, 150, ['Haircut', 'Fruit Bleach', 'Fruit Facial', 'Beard Setting']],
            ['Haircut + Hair Spa + Beard Setting', 449, 120, ['Haircut', 'Hair Spa', 'Beard Setting']],
        ];

        foreach ($packages as $index => [$name, $price, $duration, $items]) {
            $slug = Str::slug($name);
            $matchedItems = collect($items)->map(function (string $itemName) use ($services) {
                $service = $services[Str::slug($itemName)] ?? null;

                return [
                    'service' => $service,
                    'name' => $itemName,
                    'price' => $service?->price_type === 'fixed' ? $service->price : null,
                    'duration' => $service?->duration_minutes,
                ];
            });

            $regularTotal = $matchedItems->every(fn ($item) => $item['price'] !== null)
                ? $matchedItems->sum(fn ($item) => (float) $item['price'])
                : null;

            $package = Service::query()->updateOrCreate(
                ['slug' => $slug],
                [
                    'category_id' => $category->id,
                    'name' => $name,
                    'service_code' => 'PKG-'.Str::upper(Str::slug($name, '-')),
                    'short_description' => 'Signature combo package including '.implode(', ', $items).'.',
                    'detailed_description' => 'Thoughtfully combined grooming services designed to help you enjoy more care in one appointment.',
                    'price_type' => 'fixed',
                    'price' => $price,
                    'minimum_price' => null,
                    'maximum_price' => null,
                    'price_on_request' => false,
                    'currency_code' => 'INR',
                    'is_package' => true,
                    'is_salon_service_available' => true,
                    'duration_minutes' => $duration,
                    'image' => $this->placeholderFor($name, $category->name, true),
                    'is_featured' => true,
                    'is_home_service_available' => false,
                    'included_services' => $items,
                    'regular_total' => $regularTotal,
                    'savings_amount' => $regularTotal !== null && $regularTotal > $price ? $regularTotal - $price : null,
                    'gender_applicability' => 'men',
                    'status' => 'active',
                    'display_order' => ($index + 1) * 10,
                ]
            );

            $package->packageItems()->delete();
            foreach ($matchedItems as $itemIndex => $item) {
                $package->packageItems()->create([
                    'service_id' => $item['service']?->id,
                    'name_snapshot' => $item['name'],
                    'price_snapshot' => $item['price'],
                    'duration_minutes' => $item['duration'],
                    'display_order' => ($itemIndex + 1) * 10,
                ]);
            }

            $this->packageCount++;
        }
    }

    private function pricingNote(string $priceType): ?string
    {
        return match ($priceType) {
            'starting_from' => 'Estimated price. Final amount will be confirmed after consultation.',
            'range' => 'Final pricing may vary depending on colour selection, hair length and required coverage.',
            'contact' => 'Final price will be confirmed by salon staff.',
            default => null,
        };
    }

    private function placeholderFor(string $name, string $category, bool $isPackage): string
    {
        $text = Str::lower($name.' '.$category);

        return match (true) {
            $isPackage => 'images/services/svg/package.svg',
            str_contains($text, 'home') => 'images/services/svg/elite-home-service.svg',
            str_contains($text, 'beard'), str_contains($text, 'shave') => 'images/services/svg/beard-trim.svg',
            str_contains($text, 'wash') => 'images/services/svg/hair-wash.svg',
            str_contains($text, 'colour'), str_contains($text, 'color'), str_contains($text, 'henna'), str_contains($text, 'garnier'), str_contains($text, 'loreal'), str_contains($text, 'l’oréal') => 'images/services/svg/hair-colour.svg',
            str_contains($text, 'spa'), str_contains($text, 'dandruff'), str_contains($text, 'growth') => 'images/services/svg/hair-spa.svg',
            str_contains($text, 'gold') => 'images/services/svg/gold-facial.svg',
            str_contains($text, 'diamond') => 'images/services/svg/diamond-facial.svg',
            str_contains($text, 'facial'), str_contains($text, 'cleanup'), str_contains($text, 'skin') => 'images/services/svg/facial.svg',
            str_contains($text, 'massage'), str_contains($text, 'oil') => 'images/services/svg/oil-massage.svg',
            str_contains($text, 'tan'), str_contains($text, 'bleach') => 'images/services/svg/de-tan.svg',
            str_contains($text, 'piercing') => 'images/services/svg/ear-piercing.svg',
            default => 'images/services/svg/haircut.svg',
        };
    }
}
