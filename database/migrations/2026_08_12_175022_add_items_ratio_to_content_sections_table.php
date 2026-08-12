<?php

use App\Models\ContentSection;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Gambar kartu selalu dipotong 4:3. Perbandingan sisinya kini bisa dipilih
 * admin — foto kegiatan enak dilihat melebar, poster justru butuh potret.
 * Bawaannya tetap 4:3 agar seksi yang sudah ada tidak berubah.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('content_sections', function (Blueprint $table): void {
            $table->string('items_ratio', 10)->default(ContentSection::DEFAULT_CARD_RATIO)->after('items_columns');
        });
    }

    public function down(): void
    {
        Schema::table('content_sections', function (Blueprint $table): void {
            $table->dropColumn('items_ratio');
        });
    }
};
