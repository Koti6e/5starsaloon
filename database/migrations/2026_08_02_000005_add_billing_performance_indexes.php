<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('services', function (Blueprint $table): void {
            $table->index('name', 'services_name_index');
            $table->index(['status', 'is_salon_service_available', 'is_package'], 'services_billing_filter_index');
        });

        Schema::table('payments', function (Blueprint $table): void {
            $table->index(['paid_at', 'payment_method'], 'payments_paid_method_index');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table): void {
            $table->dropIndex('payments_paid_method_index');
        });

        Schema::table('services', function (Blueprint $table): void {
            $table->dropIndex('services_billing_filter_index');
            $table->dropIndex('services_name_index');
        });
    }
};
