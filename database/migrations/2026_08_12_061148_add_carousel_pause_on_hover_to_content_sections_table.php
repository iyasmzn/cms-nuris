<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Carousel selalu berhenti saat disorot kursor. Perilaku itu kini bisa dipilih:
 * ada seksi yang justru harus terus berjalan meski kursor lewat di atasnya.
 * Default menyala agar seksi yang sudah ada tidak berubah perilakunya.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('content_sections', function (Blueprint $table): void {
            $table->boolean('carousel_pause_on_hover')->default(true)->after('carousel_autoplay_delay');
        });
    }

    public function down(): void
    {
        Schema::table('content_sections', function (Blueprint $table): void {
            $table->dropColumn('carousel_pause_on_hover');
        });
    }
};
