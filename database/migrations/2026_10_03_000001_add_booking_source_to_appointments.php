<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('appointments', 'booking_source')) {
            Schema::table('appointments', function (Blueprint $table): void {
                $table->string('booking_source', 30)->default('STAFF_BOOKING')->index();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('appointments', 'booking_source')) {
            Schema::table('appointments', fn (Blueprint $table) => $table->dropColumn('booking_source'));
        }
    }
};
