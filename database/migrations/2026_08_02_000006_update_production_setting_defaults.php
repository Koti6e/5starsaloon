<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $settings = [
            'invoice_prefix' => '5STAR',
            'default_theme' => 'light',
            'whatsapp_floater_enabled' => '0',
            'whatsapp_default_message' => 'Hello, I would like to know more about your salon services.',
            'promotion_enabled' => '0',
            'promotion_button_text' => 'Book Now',
            'promotion_button_link' => '/book-appointment',
        ];

        foreach ($settings as $key => $value) {
            DB::table('salon_settings')->updateOrInsert(
                ['key' => $key],
                ['value' => $value, 'updated_at' => now(), 'created_at' => now()],
            );
        }
    }

    public function down(): void
    {
        //
    }
};
