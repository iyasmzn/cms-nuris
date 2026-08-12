<?php

use App\Models\ContentSection;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pola latar kini boleh bergerak: hanyut perlahan, berdenyut, atau bergeser
 * mengikuti guliran. Bawaannya mati — pola diam tetap jadi perilaku normal,
 * dan animasi apa pun otomatis padam bagi pengunjung yang meminta gerak
 * seminimal mungkin lewat setelan sistemnya.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('content_sections', function (Blueprint $table): void {
            $table->boolean('background_pattern_animated')->default(false)->after('background_pattern_opacity');
            $table->string('background_pattern_motion', 20)
                ->default(ContentSection::DEFAULT_PATTERN_MOTION)
                ->after('background_pattern_animated');
            $table->unsignedSmallInteger('background_pattern_speed')
                ->default(ContentSection::DEFAULT_PATTERN_SPEED)
                ->after('background_pattern_motion');
        });
    }

    public function down(): void
    {
        Schema::table('content_sections', function (Blueprint $table): void {
            $table->dropColumn([
                'background_pattern_animated',
                'background_pattern_motion',
                'background_pattern_speed',
            ]);
        });
    }
};
