<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bills', function (Blueprint $table): void {
            if (! Schema::hasColumn('bills', 'invoice_public_token')) {
                $table->uuid('invoice_public_token')->nullable()->unique()->after('idempotency_key');
            }

            if (! Schema::hasColumn('bills', 'invoice_pdf_path')) {
                $table->string('invoice_pdf_path')->nullable()->after('invoice_public_token');
            }
        });

        Schema::table('staff_attendances', function (Blueprint $table): void {
            if (! Schema::hasColumn('staff_attendances', 'login_session_id')) {
                $table->string('login_session_id')->nullable()->after('source')->index();
            }

            if (! Schema::hasColumn('staff_attendances', 'selfie_path')) {
                $table->string('selfie_path')->nullable()->after('login_session_id');
            }

            if (! Schema::hasColumn('staff_attendances', 'selfie_captured_at')) {
                $table->timestamp('selfie_captured_at')->nullable()->after('selfie_path');
            }
        });
    }

    public function down(): void
    {
        Schema::table('staff_attendances', function (Blueprint $table): void {
            foreach (['selfie_captured_at', 'selfie_path', 'login_session_id'] as $column) {
                if (Schema::hasColumn('staff_attendances', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('bills', function (Blueprint $table): void {
            foreach (['invoice_pdf_path', 'invoice_public_token'] as $column) {
                if (Schema::hasColumn('bills', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
