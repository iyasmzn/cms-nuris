<?php

use App\Models\ContentSection;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Kecepatan gerak pola semula tersimpan sebagai lama satu putaran (detik).
 * Ukuran itu menyesatkan: satu putaran hanya sejauh satu ubin (16–60px),
 * sehingga "40 detik" berarti 1,5 piksel per detik — polanya terlihat diam.
 * Nilainya kini piksel per detik, dan pilihan lama dipetakan ke label yang sama.
 */
return new class extends Migration
{
    /** Detik-per-putaran lama → piksel-per-detik baru, berurut label yang sama. */
    private const REMAP = [
        90 => 3,   // Sangat Lambat
        60 => 6,   // Lambat
        40 => 12,  // Sedang
        25 => 20,  // Cepat
    ];

    public function up(): void
    {
        foreach (self::REMAP as $old => $new) {
            DB::table('content_sections')
                ->where('background_pattern_speed', $old)
                ->update(['background_pattern_speed' => $new]);
        }

        Schema::table('content_sections', function (Blueprint $table): void {
            $table->unsignedSmallInteger('background_pattern_speed')
                ->default(ContentSection::DEFAULT_PATTERN_SPEED)
                ->change();
        });
    }

    public function down(): void
    {
        foreach (array_flip(self::REMAP) as $new => $old) {
            DB::table('content_sections')
                ->where('background_pattern_speed', $new)
                ->update(['background_pattern_speed' => $old]);
        }

        Schema::table('content_sections', function (Blueprint $table): void {
            $table->unsignedSmallInteger('background_pattern_speed')->default(60)->change();
        });
    }
};
