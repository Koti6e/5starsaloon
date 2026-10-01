<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('salon_settings')->where('key', 'salon_name')->where('value', '5 Star New Look Salon')->update(['value' => '5 Star New Look A/C']);
        DB::table('salon_settings')->where('key', 'address')->where('value', 'Visit the salon for location details.')->update(['value' => null]);
        DB::table('salon_settings')->where('key', 'working_hours')->where('value', 'Open daily by appointment.')->update(['value' => null]);
        DB::table('salon_settings')->where('key', 'weekly_holiday')->where('value', 'Confirmed by the salon team.')->update(['value' => null]);
        Cache::forget('salon_settings');
    }

    public function down(): void
    {
        // Placeholder values are intentionally not restored.
    }
};
