<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bills', function (Blueprint $table) {
            $table->string('whatsapp_invoice_status', 20)
                ->default('pending')
                ->index();

            $table->timestamp('whatsapp_invoice_sent_at')->nullable();
            $table->string('whatsapp_invoice_message_id')->nullable();
            $table->text('whatsapp_invoice_error')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('bills', function (Blueprint $table) {
            $table->dropColumn([
                'whatsapp_invoice_status',
                'whatsapp_invoice_sent_at',
                'whatsapp_invoice_message_id',
                'whatsapp_invoice_error',
            ]);
        });
    }
};
