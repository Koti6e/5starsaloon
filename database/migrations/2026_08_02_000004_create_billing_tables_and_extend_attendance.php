<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('staff_attendances', function (Blueprint $table): void {
            if (! Schema::hasColumn('staff_attendances', 'source')) {
                $table->string('source', 30)->default('manual_admin')->after('check_out_time')->index();
            }
            if (! Schema::hasColumn('staff_attendances', 'corrected_by')) {
                $table->foreignId('corrected_by')->nullable()->after('marked_by')->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('staff_attendances', 'correction_reason')) {
                $table->text('correction_reason')->nullable()->after('corrected_by');
            }
            if (! Schema::hasColumn('staff_attendances', 'corrected_at')) {
                $table->timestamp('corrected_at')->nullable()->after('correction_reason');
            }
        });

        Schema::create('bills', function (Blueprint $table): void {
            $table->id();
            $table->string('invoice_number')->unique();
            $table->foreignId('customer_id')->constrained('customers')->restrictOnDelete();
            $table->foreignId('billed_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->decimal('subtotal', 12, 2);
            $table->decimal('discount_amount', 12, 2)->default(0);
            $table->decimal('home_visit_charge', 12, 2)->default(0);
            $table->decimal('grand_total', 12, 2);
            $table->string('payment_status', 30)->default('paid')->index();
            $table->string('status', 30)->default('completed')->index();
            $table->string('idempotency_key')->nullable()->unique();
            $table->text('notes')->nullable();
            $table->timestamp('billed_at')->index();
            $table->timestamps();
        });

        Schema::create('bill_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('bill_id')->constrained('bills')->cascadeOnDelete();
            $table->foreignId('service_id')->nullable()->constrained('services')->nullOnDelete();
            $table->foreignId('service_performed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('service_name_snapshot');
            $table->string('service_code_snapshot')->nullable();
            $table->string('category_name_snapshot')->nullable();
            $table->boolean('is_package_snapshot')->default(false);
            $table->unsignedInteger('quantity')->default(1);
            $table->decimal('unit_price', 12, 2);
            $table->decimal('discount_amount', 12, 2)->default(0);
            $table->decimal('line_total', 12, 2);
            $table->boolean('price_was_confirmed')->default(true);
            $table->timestamps();
        });

        Schema::create('payments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('bill_id')->constrained('bills')->cascadeOnDelete();
            $table->string('payment_method', 30);
            $table->decimal('amount', 12, 2);
            $table->string('transaction_reference')->nullable();
            $table->string('method_note')->nullable();
            $table->foreignId('received_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('paid_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
        Schema::dropIfExists('bill_items');
        Schema::dropIfExists('bills');

        Schema::table('staff_attendances', function (Blueprint $table): void {
            foreach (['corrected_at', 'correction_reason', 'corrected_by', 'source'] as $column) {
                if (Schema::hasColumn('staff_attendances', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
