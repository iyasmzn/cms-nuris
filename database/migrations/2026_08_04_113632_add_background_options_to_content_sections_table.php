<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('content_sections', function (Blueprint $table) {
            $table->string('background_image')->nullable()->after('background');
            $table->unsignedTinyInteger('background_blur')->default(0)->after('background_image')->comment('Radius blur dalam px, 0 = tanpa blur');
            $table->unsignedTinyInteger('background_overlay')->default(0)->after('background_blur')->comment('Kepekatan lapisan gelap dalam persen');
            $table->boolean('background_parallax')->default(false)->after('background_overlay');
            $table->unsignedTinyInteger('background_parallax_speed')->default(30)->after('background_parallax')->comment('Intensitas parallax dalam persen');
            $table->boolean('background_light_text')->default(true)->after('background_parallax_speed')->comment('Teks putih di atas latar gambar');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('content_sections', function (Blueprint $table) {
            $table->dropColumn([
                'background_image',
                'background_blur',
                'background_overlay',
                'background_parallax',
                'background_parallax_speed',
                'background_light_text',
            ]);
        });
    }
};
