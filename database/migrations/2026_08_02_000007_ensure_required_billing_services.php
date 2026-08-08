<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();
        $categories = [
            'haircuts-grooming' => [
                'name' => 'Haircuts & Grooming',
                'description' => 'Professional haircut, shave, beard and grooming essentials for men.',
                'display_order' => 10,
                'image' => 'images/services/haircuts-grooming.webp',
            ],
            'facial-cleanup' => [
                'name' => 'Facial & Cleanup',
                'description' => 'Facial and cleanup services designed to refresh the appearance of the skin.',
                'display_order' => 20,
                'image' => 'images/services/facial-cleanup.webp',
            ],
            'hair-colouring' => [
                'name' => 'Hair Colouring',
                'description' => 'Hair colour and highlighting services with consultation-led finish and coverage.',
                'display_order' => 30,
                'image' => 'images/services/hair-colouring.webp',
            ],
            'oil-massage' => [
                'name' => 'Oil Massage',
                'description' => 'Relaxing head oil massage options focused on comfort and grooming.',
                'display_order' => 40,
                'image' => 'images/services/oil-massage.webp',
            ],
            'hair-skin-treatments' => [
                'name' => 'Hair & Skin Treatments',
                'description' => 'Care-focused hair and skin services with final selection guided by staff.',
                'display_order' => 50,
                'image' => 'images/services/hair-skin-treatments.webp',
            ],
        ];

        foreach ($categories as $slug => $category) {
            DB::table('service_categories')->updateOrInsert(
                ['slug' => $slug],
                [
                    ...$category,
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                    'deleted_at' => null,
                ],
            );
        }

        $categoryIds = DB::table('service_categories')->whereIn('slug', array_keys($categories))->pluck('id', 'slug');
        $services = [
            ['haircuts-grooming', 'Haircut', 120, 30, 'A professional haircut shaped around the customer’s preferred style.', 50],
            ['haircuts-grooming', 'Beard Trim', 80, 20, 'Beard shaping and trimming for a clean, maintained look.', 30],
            ['haircuts-grooming', 'Hair Wash', 60, 15, 'A clean hair wash service to refresh the scalp and prepare hair for styling.', 10],
            ['hair-colouring', 'Hair Colour', 350, 45, 'Hair colour application with shade selection confirmed by salon staff.', 10],
            ['hair-skin-treatments', 'Hair Spa', 449, 60, 'Hair spa care with final recommendation confirmed by salon staff.', 20],
            ['facial-cleanup', 'Facial', 500, 45, 'A facial service designed to refresh the appearance of the skin.', 10],
            ['facial-cleanup', 'Gold Facial', 1000, 60, 'A premium gold facial service for a refreshed and well-groomed appearance.', 20],
            ['facial-cleanup', 'Diamond Facial', 1100, 60, 'A premium facial service focused on clean, polished-looking skin.', 30],
            ['oil-massage', 'Oil Massage', 150, 30, 'A relaxing head oil massage service.', 10],
            ['facial-cleanup', 'De Tan', 350, 30, 'A de-tan care service for a refreshed grooming finish.', 40],
            ['hair-skin-treatments', 'Ear Piercing', 50, 15, 'Ear piercing service with simple aftercare guidance from staff.', 60],
        ];

        foreach ($services as [$categorySlug, $name, $price, $duration, $description, $displayOrder]) {
            $slug = Str::slug($name);

            DB::table('services')->updateOrInsert(
                ['slug' => $slug],
                [
                    'category_id' => $categoryIds[$categorySlug],
                    'name' => $name,
                    'service_code' => 'SVC-'.Str::upper(Str::slug($name, '-')),
                    'short_description' => $description,
                    'detailed_description' => $description,
                    'price_type' => 'fixed',
                    'price' => $price,
                    'minimum_price' => null,
                    'maximum_price' => null,
                    'price_on_request' => false,
                    'currency_code' => 'INR',
                    'is_package' => false,
                    'is_salon_service_available' => true,
                    'discounted_price' => null,
                    'duration_minutes' => $duration,
                    'image' => null,
                    'is_featured' => in_array($name, ['Haircut', 'Beard Trim', 'Gold Facial', 'Oil Massage'], true),
                    'is_home_service_available' => false,
                    'home_service_price' => null,
                    'home_service_visit_charge' => null,
                    'pricing_note' => null,
                    'included_services' => null,
                    'regular_total' => null,
                    'savings_amount' => null,
                    'gender_applicability' => 'men',
                    'status' => 'active',
                    'display_order' => $displayOrder,
                    'updated_at' => $now,
                    'deleted_at' => null,
                ],
            );
        }
    }
};
