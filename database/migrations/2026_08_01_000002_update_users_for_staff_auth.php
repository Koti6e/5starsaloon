<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            if (! Schema::hasColumn('users', 'username')) {
                $table->string('username')->nullable()->unique()->after('name');
            }

            if (! Schema::hasColumn('users', 'mobile')) {
                $table->string('mobile')->nullable()->index()->after('email');
            }

            if (! Schema::hasColumn('users', 'role')) {
                $table->string('role', 20)->default('staff')->index()->after('mobile');
            }

            if (! Schema::hasColumn('users', 'status')) {
                $table->string('status', 20)->default('active')->index()->after('role');
            }

            if (! Schema::hasColumn('users', 'must_change_password')) {
                $table->boolean('must_change_password')->default(false)->after('status');
            }

            if (! Schema::hasColumn('users', 'employee_code')) {
                $table->string('employee_code')->nullable()->unique()->after('must_change_password');
            }

            if (! Schema::hasColumn('users', 'profile_photo')) {
                $table->string('profile_photo')->nullable()->after('employee_code');
            }

            if (! Schema::hasColumn('users', 'specialization')) {
                $table->string('specialization')->nullable()->after('profile_photo');
            }

            if (! Schema::hasColumn('users', 'joining_date')) {
                $table->date('joining_date')->nullable()->after('specialization');
            }

            if (! Schema::hasColumn('users', 'employment_type')) {
                $table->string('employment_type')->nullable()->after('joining_date');
            }

            if (! Schema::hasColumn('users', 'address')) {
                $table->text('address')->nullable()->after('employment_type');
            }

            if (! Schema::hasColumn('users', 'emergency_contact')) {
                $table->string('emergency_contact')->nullable()->after('address');
            }

            if (! Schema::hasColumn('users', 'is_home_service_eligible')) {
                $table->boolean('is_home_service_eligible')->default(false)->after('emergency_contact');
            }

            if (! Schema::hasColumn('users', 'shift_start')) {
                $table->time('shift_start')->nullable()->after('is_home_service_eligible');
            }

            if (! Schema::hasColumn('users', 'shift_end')) {
                $table->time('shift_end')->nullable()->after('shift_start');
            }

            if (! Schema::hasColumn('users', 'weekly_off')) {
                $table->string('weekly_off')->nullable()->after('shift_end');
            }
        });

        if (Schema::hasColumn('users', 'username')) {
            DB::table('users')
                ->whereNull('username')
                ->orderBy('id')
                ->eachById(fn ($user) => DB::table('users')
                    ->where('id', $user->id)
                    ->update(['username' => 'user'.$user->id]));
        }

        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE users MODIFY email VARCHAR(255) NULL');
        }

        Schema::table('users', function (Blueprint $table): void {
            if (! Schema::hasColumn('users', 'created_by')) {
                $table->foreignId('created_by')->nullable()->after('weekly_off')->constrained('users')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            foreach ([
                'created_by',
                'weekly_off',
                'shift_end',
                'shift_start',
                'is_home_service_eligible',
                'emergency_contact',
                'address',
                'employment_type',
                'joining_date',
                'specialization',
                'profile_photo',
                'employee_code',
                'must_change_password',
                'status',
                'role',
                'mobile',
                'username',
            ] as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
