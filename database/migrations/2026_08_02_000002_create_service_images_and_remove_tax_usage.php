<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_images', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('service_id')->constrained('services')->cascadeOnDelete();
            $table->string('image_path');
            $table->string('thumbnail_path')->nullable();
            $table->string('alt_text');
            $table->boolean('is_cover')->default(false)->index();
            $table->unsignedTinyInteger('sort_order')->default(0);
            $table->timestamps();
            $table->index(['service_id', 'sort_order']);
        });

        $formerChargePrefix = implode('', ['t', 'a', 'x']);
        $formerRegistrationPrefix = implode('', ['g', 's', 't']);

        DB::table('salon_settings')
            ->whereIn('key', [
                $formerChargePrefix.'_enabled',
                $formerChargePrefix.'_label',
                $formerChargePrefix.'_percentage',
                $formerRegistrationPrefix.'_number',
                $formerRegistrationPrefix.'in',
            ])
            ->delete();

        $formerServiceColumn = $formerChargePrefix.'_inclusive';

        if (Schema::hasColumn('services', $formerServiceColumn)) {
            Schema::table('services', function (Blueprint $table): void {
                $table->dropColumn(implode('', ['t', 'a', 'x']).'_inclusive');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('service_images');

        //
    }
};
