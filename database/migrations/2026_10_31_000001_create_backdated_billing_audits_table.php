<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('backdated_billing_audits')) {
            return;
        }

        Schema::create('backdated_billing_audits', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('bill_id')->constrained('bills')->restrictOnDelete();
            $table->foreignId('admin_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('business_date');
            $table->text('reason');
            $table->timestamp('created_at')->useCurrent();
            $table->index(['business_date', 'created_at']);
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('backdated_billing_audits') && DB::table('backdated_billing_audits')->exists()) {
            throw new RuntimeException('Backdated billing audit records exist; archive them before rolling back this migration.');
        }
        Schema::dropIfExists('backdated_billing_audits');
    }
};
