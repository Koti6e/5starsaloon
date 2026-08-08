<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bills', function (Blueprint $table): void {
            if (! Schema::hasColumn('bills', 'appointment_id')) {
                $table->foreignId('appointment_id')->nullable()->after('customer_id')->constrained('appointments')->nullOnDelete();
            }

            if (! Schema::hasColumn('bills', 'appointment_booking_number')) {
                $table->string('appointment_booking_number')->nullable()->after('appointment_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('bills', function (Blueprint $table): void {
            if (Schema::hasColumn('bills', 'appointment_booking_number')) {
                $table->dropColumn('appointment_booking_number');
            }

            if (Schema::hasColumn('bills', 'appointment_id')) {
                $table->dropForeign(['appointment_id']);
                $table->dropColumn('appointment_id');
            }
        });
    }
};
