<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('attendance_verification_attempts', 'attempt_type')) {
            Schema::table('attendance_verification_attempts', function (Blueprint $table): void {
                $table->string('attempt_type', 24)->default('attendance_selfie')->after('device_id')->index();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('attendance_verification_attempts', 'attempt_type')) {
            Schema::table('attendance_verification_attempts', function (Blueprint $table): void {
                $table->dropIndex(['attempt_type']);
                $table->dropColumn('attempt_type');
            });
        }
    }
};
