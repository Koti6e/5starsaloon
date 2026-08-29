<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table): void {
            if (! Schema::hasColumn('customers', 'membership_id')) {
                $table->string('membership_id')->nullable()->after('mobile')->index();
            }

            if (! Schema::hasColumn('customers', 'branch')) {
                $table->string('branch')->nullable()->after('membership_id')->index();
            }

            if (! Schema::hasColumn('customers', 'whatsapp_status')) {
                $table->string('whatsapp_status', 30)->default('not_contacted')->after('status')->index();
            }

            if (! Schema::hasColumn('customers', 'source')) {
                $table->string('source')->nullable()->after('whatsapp_status')->index();
            }

            if (! Schema::hasColumn('customers', 'source_reference')) {
                $table->string('source_reference')->nullable()->after('source');
            }

            if (! Schema::hasColumn('customers', 'imported_at')) {
                $table->timestamp('imported_at')->nullable()->after('source_reference');
            }
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table): void {
            foreach (['imported_at', 'source_reference', 'source', 'whatsapp_status', 'branch', 'membership_id'] as $column) {
                if (Schema::hasColumn('customers', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
