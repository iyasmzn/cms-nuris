<?php

use App\Models\ContentSection;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Latar polos kini bisa dihias pola SVG (titik, garis, gelombang, ornamen)
 * yang diwarnai mengikuti warna utama tema. Bawaannya `none`, jadi seksi yang
 * sudah ada tetap polos seperti sebelumnya.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('content_sections', function (Blueprint $table): void {
            $table->string('background_pattern', 20)->default('none')->after('background_image');
            $table->unsignedTinyInteger('background_pattern_opacity')
                ->default(ContentSection::DEFAULT_PATTERN_OPACITY)
                ->after('background_pattern');
        });
    }

    public function down(): void
    {
        Schema::table('content_sections', function (Blueprint $table): void {
            $table->dropColumn(['background_pattern', 'background_pattern_opacity']);
        });
    }
};
