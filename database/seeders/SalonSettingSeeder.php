<?php

namespace Database\Seeders;

use App\Models\SalonSetting;
use Illuminate\Database\Seeder;

class SalonSettingSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            'salon_name' => '5 Star New Look Salon',
            'tagline' => 'Look Good. Feel Great. Be Confident.',
            'logo' => 'images/brand/logo-full.webp',
            'logo_mark' => 'images/brand/logo-mark.webp',
            'favicon' => 'favicon.ico',
            'address' => 'Visit the salon for location details.',
            'primary_phone' => '',
            'whatsapp_number' => '',
            'whatsapp_floater_enabled' => '0',
            'whatsapp_default_message' => 'Hello, I would like to know more about your salon services.',
            'email' => '',
            'working_hours' => 'Open daily by appointment.',
            'weekly_holiday' => 'Confirmed by the salon team.',
            'google_maps_url' => '',
            'currency' => 'INR',
            'invoice_prefix' => '5STAR',
            'default_theme' => 'emerald',
            'next_invoice_number' => '1',
            'appointment_slot_duration' => '30',
            'default_home_visit_charge' => '0',
            'invoice_footer_text' => 'Thank you for choosing 5 Star New Look Salon. Look Good. Feel Great. Be Confident.',
            'invoice_thank_you_message' => 'Thank you for visiting 5 Star New Look Salon.',
            'promotion_enabled' => '0',
            'promotion_title' => '',
            'promotion_subtitle' => '',
            'promotion_offer_price' => '',
            'promotion_start_date' => '',
            'promotion_end_date' => '',
            'promotion_button_text' => 'Book Now',
            'promotion_button_link' => '/book-appointment',
            'promotion_image' => '',
        ];

        foreach ($settings as $key => $value) {
            SalonSetting::putValue($key, $value);
        }
    }
}
