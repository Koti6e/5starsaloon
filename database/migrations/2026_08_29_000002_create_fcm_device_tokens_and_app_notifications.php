<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fcm_device_tokens', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->text('token');
            $table->string('token_hash', 64)->unique();
            $table->string('platform', 30)->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamp('revoked_at')->nullable()->index();
            $table->timestamps();
            $table->index(['user_id', 'revoked_at']);
        });

        Schema::create('app_notifications', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('appointment_id')->nullable()->constrained('appointments')->cascadeOnDelete();
            $table->string('type', 80)->index();
            $table->string('title');
            $table->text('body');
            $table->string('action_url')->nullable();
            $table->json('payload')->nullable();
            $table->timestamp('read_at')->nullable()->index();
            $table->timestamp('fcm_sent_at')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'appointment_id', 'type'], 'app_notifications_once_per_event');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('app_notifications');
        Schema::dropIfExists('fcm_device_tokens');
    }
};
