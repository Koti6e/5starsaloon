<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('services', function (Blueprint $table): void {
            if (! Schema::hasColumn('services', 'price_type')) {
                $table->string('price_type', 30)->default('fixed')->after('detailed_description')->index();
            }

            if (! Schema::hasColumn('services', 'minimum_price')) {
                $table->decimal('minimum_price', 10, 2)->nullable()->after('price');
            }

            if (! Schema::hasColumn('services', 'maximum_price')) {
                $table->decimal('maximum_price', 10, 2)->nullable()->after('minimum_price');
            }

            if (! Schema::hasColumn('services', 'price_on_request')) {
                $table->boolean('price_on_request')->default(false)->after('maximum_price')->index();
            }

            if (! Schema::hasColumn('services', 'currency_code')) {
                $table->string('currency_code', 3)->default('INR')->after('price_on_request');
            }

            if (! Schema::hasColumn('services', 'is_package')) {
                $table->boolean('is_package')->default(false)->after('currency_code')->index();
            }

            if (! Schema::hasColumn('services', 'is_salon_service_available')) {
                $table->boolean('is_salon_service_available')->default(true)->after('is_package')->index();
            }

            if (! Schema::hasColumn('services', 'pricing_note')) {
                $table->text('pricing_note')->nullable()->after('home_service_visit_charge');
            }

            if (! Schema::hasColumn('services', 'included_services')) {
                $table->json('included_services')->nullable()->after('pricing_note');
            }

            if (! Schema::hasColumn('services', 'regular_total')) {
                $table->decimal('regular_total', 10, 2)->nullable()->after('included_services');
            }

            if (! Schema::hasColumn('services', 'savings_amount')) {
                $table->decimal('savings_amount', 10, 2)->nullable()->after('regular_total');
            }
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE services MODIFY price DECIMAL(10, 2) NULL');
            DB::statement('ALTER TABLE services MODIFY duration_minutes SMALLINT UNSIGNED NULL');
        }

        Schema::create('service_package_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('package_id')->constrained('services')->cascadeOnDelete();
            $table->foreignId('service_id')->nullable()->constrained('services')->nullOnDelete();
            $table->string('name_snapshot');
            $table->decimal('price_snapshot', 10, 2)->nullable();
            $table->unsignedSmallInteger('duration_minutes')->nullable();
            $table->unsignedInteger('display_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_package_items');

        Schema::table('services', function (Blueprint $table): void {
            foreach ([
                'savings_amount',
                'regular_total',
                'included_services',
                'pricing_note',
                'is_salon_service_available',
                'is_package',
                'currency_code',
                'price_on_request',
                'maximum_price',
                'minimum_price',
                'price_type',
            ] as $column) {
                if (Schema::hasColumn('services', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
