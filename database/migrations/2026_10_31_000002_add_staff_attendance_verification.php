<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('staff_attendances', function (Blueprint $table): void {
            if (! Schema::hasColumn('staff_attendances', 'latitude')) $table->decimal('latitude', 10, 7)->nullable();
            if (! Schema::hasColumn('staff_attendances', 'longitude')) $table->decimal('longitude', 10, 7)->nullable();
            if (! Schema::hasColumn('staff_attendances', 'gps_accuracy_meters')) $table->decimal('gps_accuracy_meters', 8, 2)->nullable();
            if (! Schema::hasColumn('staff_attendances', 'distance_from_salon_meters')) $table->decimal('distance_from_salon_meters', 9, 2)->nullable();
            if (! Schema::hasColumn('staff_attendances', 'verification_status')) $table->string('verification_status', 24)->default('review_required')->index();
            if (! Schema::hasColumn('staff_attendances', 'verification_reason')) $table->text('verification_reason')->nullable();
            if (! Schema::hasColumn('staff_attendances', 'face_verification_status')) $table->string('face_verification_status', 24)->default('unavailable');
            if (! Schema::hasColumn('staff_attendances', 'consent_at')) $table->timestamp('consent_at')->nullable();
        });

        if (! Schema::hasTable('staff_attendance_devices')) {
            Schema::create('staff_attendance_devices', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('staff_id')->constrained('users')->cascadeOnUpdate()->cascadeOnDelete();
                $table->string('device_key_hash', 64);
                $table->string('label', 100)->nullable();
                $table->string('status', 16)->default('pending')->index();
                $table->timestamp('first_seen_at');
                $table->timestamp('last_seen_at');
                $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('approved_at')->nullable();
                $table->foreignId('revoked_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('revoked_at')->nullable();
                $table->timestamps();
                $table->unique(['staff_id', 'device_key_hash']);
            });
        }

        if (! Schema::hasTable('attendance_verification_attempts')) {
            Schema::create('attendance_verification_attempts', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('staff_id')->constrained('users')->cascadeOnUpdate()->cascadeOnDelete();
                $table->foreignId('attendance_id')->nullable()->constrained('staff_attendances')->nullOnDelete();
                $table->foreignId('device_id')->nullable()->constrained('staff_attendance_devices')->nullOnDelete();
                $table->decimal('latitude', 10, 7)->nullable();
                $table->decimal('longitude', 10, 7)->nullable();
                $table->decimal('gps_accuracy_meters', 8, 2)->nullable();
                $table->decimal('distance_from_salon_meters', 9, 2)->nullable();
                $table->string('decision', 24)->index();
                $table->text('reason')->nullable();
                $table->string('selfie_path')->nullable();
                $table->timestamp('attempted_at');
                $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('reviewed_at')->nullable();
                $table->string('review_decision', 24)->nullable();
                $table->text('review_reason')->nullable();
                $table->timestamps();
                $table->index(['staff_id', 'attempted_at']);
            });
        }

        if (! Schema::hasTable('staff_attendance_device_events')) {
            Schema::create('staff_attendance_device_events', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('device_id')->constrained('staff_attendance_devices')->cascadeOnUpdate()->cascadeOnDelete();
                $table->foreignId('admin_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('previous_status', 16)->nullable();
                $table->string('action', 24);
                $table->text('reason');
                $table->timestamp('created_at')->useCurrent();
                $table->index(['device_id', 'created_at']);
            });
        }

        if (! Schema::hasTable('staff_attendance_audits')) {
            Schema::create('staff_attendance_audits', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('attendance_id')->nullable()->constrained('staff_attendances')->nullOnDelete();
                $table->foreignId('staff_id')->constrained('users')->cascadeOnUpdate()->cascadeOnDelete();
                $table->foreignId('admin_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('action', 24);
                $table->string('previous_status', 20)->nullable();
                $table->string('new_status', 20)->nullable();
                $table->string('previous_verification_status', 24)->nullable();
                $table->string('new_verification_status', 24)->nullable();
                $table->text('reason');
                $table->timestamp('created_at')->useCurrent();
                $table->index(['staff_id', 'created_at']);
            });
        }

        Schema::table('staff_attendances', function (Blueprint $table): void {
            if (! Schema::hasColumn('staff_attendances', 'device_id')) {
                $table->foreignId('device_id')->nullable()->constrained('staff_attendance_devices')->nullOnDelete();
            }
        });

        $defaults = [
            'latitude' => '12.8821489',
            'longitude' => '80.0808760',
            'attendance_geofence_radius_meters' => '200',
            'attendance_gps_accuracy_threshold_meters' => '100',
        ];
        foreach ($defaults as $key => $value) {
            if (! DB::table('salon_settings')->where('key', $key)->exists()) {
                DB::table('salon_settings')->insert(['key' => $key, 'value' => $value, 'created_at' => now(), 'updated_at' => now()]);
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('attendance_verification_attempts') && DB::table('attendance_verification_attempts')->exists()) {
            throw new RuntimeException('Attendance verification attempts exist; archive them before rolling back this migration.');
        }
        if (Schema::hasTable('staff_attendance_devices') && DB::table('staff_attendance_devices')->exists()) {
            throw new RuntimeException('Attendance devices exist; archive them before rolling back this migration.');
        }
        if (Schema::hasTable('staff_attendance_device_events') && DB::table('staff_attendance_device_events')->exists()) {
            throw new RuntimeException('Attendance device audit events exist; archive them before rolling back this migration.');
        }
        if (Schema::hasTable('staff_attendance_audits') && DB::table('staff_attendance_audits')->exists()) {
            throw new RuntimeException('Attendance audit events exist; archive them before rolling back this migration.');
        }
        if (Schema::hasColumn('staff_attendances', 'latitude') && DB::table('staff_attendances')->whereNotNull('latitude')->exists()) {
            throw new RuntimeException('Verified attendance data exists; archive it before rolling back this migration.');
        }

        Schema::table('staff_attendances', function (Blueprint $table): void {
            if (Schema::hasColumn('staff_attendances', 'device_id')) $table->dropConstrainedForeignId('device_id');
        });
        Schema::dropIfExists('attendance_verification_attempts');
        Schema::dropIfExists('staff_attendance_audits');
        Schema::dropIfExists('staff_attendance_device_events');
        Schema::dropIfExists('staff_attendance_devices');
        Schema::table('staff_attendances', function (Blueprint $table): void {
            foreach (['latitude', 'longitude', 'gps_accuracy_meters', 'distance_from_salon_meters', 'verification_status', 'verification_reason', 'face_verification_status', 'consent_at'] as $column) {
                if (Schema::hasColumn('staff_attendances', $column)) $table->dropColumn($column);
            }
        });
    }
};
