<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bills', function (Blueprint $table): void {
            if (! Schema::hasColumn('bills', 'billing_date')) {
                $table->date('billing_date')->nullable()->after('billed_at')->index();
            }
        });

        DB::table('bills')->whereNull('billing_date')->update([
            'billing_date' => DB::raw('DATE(billed_at)'),
        ]);
    }

    public function down(): void
    {
        Schema::table('bills', function (Blueprint $table): void {
            if (Schema::hasColumn('bills', 'billing_date')) $table->dropColumn('billing_date');
        });
    }
};
