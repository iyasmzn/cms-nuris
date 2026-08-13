<?php

use App\Models\ContentSection;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Warna pola semula terkunci ke warna utama tema. Kini bisa dipilih: ikut token
 * tema (utama, teks, abu), putih untuk latar gelap, atau hex sendiri.
 * Bawaannya `primary` — persis perilaku sebelumnya.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('content_sections', function (Blueprint $table): void {
            $table->string('background_pattern_color', 20)
                ->default(ContentSection::DEFAULT_PATTERN_COLOR)
                ->after('background_pattern_scale');
            $table->string('background_pattern_custom_color', 9)
                ->nullable()
                ->after('background_pattern_color');
        });
    }

    public function down(): void
    {
        Schema::table('content_sections', function (Blueprint $table): void {
            $table->dropColumn(['background_pattern_color', 'background_pattern_custom_color']);
        });
    }
};
