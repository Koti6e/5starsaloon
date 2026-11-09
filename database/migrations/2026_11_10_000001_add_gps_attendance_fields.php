<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('staff_attendances', function (Blueprint $table): void {
            foreach (['check_in_latitude', 'check_in_longitude', 'check_out_latitude', 'check_out_longitude'] as $column) {
                if (! Schema::hasColumn('staff_attendances', $column)) $table->decimal($column, 10, 7)->nullable();
            }
            foreach (['check_in_gps_accuracy', 'check_in_distance_from_salon', 'check_out_gps_accuracy', 'check_out_distance_from_salon'] as $column) {
                if (! Schema::hasColumn('staff_attendances', $column)) $table->decimal($column, 10, 2)->nullable();
            }
            foreach (['check_in_gps_verification_status', 'check_out_gps_verification_status'] as $column) {
                if (! Schema::hasColumn('staff_attendances', $column)) $table->string($column, 32)->nullable();
            }
            foreach (['check_in_location_captured_at', 'check_out_location_captured_at'] as $column) {
                if (! Schema::hasColumn('staff_attendances', $column)) $table->timestamp($column)->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('staff_attendances', function (Blueprint $table): void {
            foreach ([
                'check_in_latitude', 'check_in_longitude', 'check_out_latitude', 'check_out_longitude',
                'check_in_gps_accuracy', 'check_in_distance_from_salon', 'check_out_gps_accuracy', 'check_out_distance_from_salon',
                'check_in_gps_verification_status', 'check_out_gps_verification_status',
                'check_in_location_captured_at', 'check_out_location_captured_at',
            ] as $column) if (Schema::hasColumn('staff_attendances', $column)) $table->dropColumn($column);
        });
    }
};
