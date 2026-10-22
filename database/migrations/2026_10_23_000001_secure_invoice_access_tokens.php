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
            if (! Schema::hasColumn('bills', 'invoice_access_token_hash')) {
                $table->char('invoice_access_token_hash', 64)->nullable();
            }
            if (! Schema::hasColumn('bills', 'invoice_access_expires_at')) {
                $table->timestamp('invoice_access_expires_at')->nullable();
            }
            if (! Schema::hasColumn('bills', 'invoice_access_revoked_at')) {
                $table->timestamp('invoice_access_revoked_at')->nullable();
            }
        });
        if (! Schema::hasIndex('bills', 'bills_invoice_access_token_hash_unique', 'unique')) {
            Schema::table('bills', fn (Blueprint $table) => $table->unique('invoice_access_token_hash'));
        }

        // Preserve existing links for a bounded transition period, but store only
        // their digest going forward. New links use random 256-bit tokens.
        DB::table('bills')->whereNotNull('invoice_public_token')->orderBy('id')->chunkById(250, function ($bills): void {
            foreach ($bills as $bill) {
                DB::table('bills')->where('id', $bill->id)->update([
                    'invoice_access_token_hash' => hash('sha256', (string) $bill->invoice_public_token),
                    'invoice_access_expires_at' => now()->addDays(90),
                ]);
            }
        });
        DB::table('bills')->whereNotNull('invoice_public_token')->update(['invoice_public_token' => null]);
    }

    public function down(): void
    {
        Schema::table('bills', function (Blueprint $table): void {
            $table->dropUnique(['invoice_access_token_hash']);
            $table->dropColumn(['invoice_access_token_hash', 'invoice_access_expires_at', 'invoice_access_revoked_at']);
        });
    }
};
