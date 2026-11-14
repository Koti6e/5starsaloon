<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('attendance_verification_attempts')
            || Schema::hasColumn('attendance_verification_attempts', 'gps_position_at')) {
            return;
        }

        Schema::table('attendance_verification_attempts', function (Blueprint $table): void {
            $table->timestamp('gps_position_at')->nullable()->after('gps_accuracy_meters');
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('attendance_verification_attempts')
            && Schema::hasColumn('attendance_verification_attempts', 'gps_position_at')) {
            Schema::table('attendance_verification_attempts', function (Blueprint $table): void {
                $table->dropColumn('gps_position_at');
            });
        }
    }
};
