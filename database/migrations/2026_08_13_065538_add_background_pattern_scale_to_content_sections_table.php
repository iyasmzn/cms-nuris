<?php

use App\Models\ContentSection;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ukuran ubin pola kini bisa dipilih (persen dari ukuran aslinya): pola yang
 * pas di seksi sempit sering terlalu rapat di seksi lebar. Bawaannya 100%,
 * jadi seksi yang sudah ada tidak berubah tampilannya.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('content_sections', function (Blueprint $table): void {
            $table->unsignedSmallInteger('background_pattern_scale')
                ->default(ContentSection::DEFAULT_PATTERN_SCALE)
                ->after('background_pattern_opacity');
        });
    }

    public function down(): void
    {
        Schema::table('content_sections', function (Blueprint $table): void {
            $table->dropColumn('background_pattern_scale');
        });
    }
};
