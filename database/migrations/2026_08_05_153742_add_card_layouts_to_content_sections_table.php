<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Seksi dinamis kini punya tiga bentuk isi: gambar berdampingan teks
     * (bawaan, seperti sebelumnya), deretan kartu, atau kartu yang berjalan
     * dalam carousel. Kartunya disimpan sebagai JSON karena hanya dipakai oleh
     * seksinya sendiri dan urutannya diatur langsung di dalam formulir.
     */
    public function up(): void
    {
        Schema::table('content_sections', function (Blueprint $table) {
            $table->string('layout', 20)->default('media')->after('description');
            $table->json('items')->nullable()->after('image_position');
            $table->unsignedTinyInteger('items_columns')->default(3)->after('items');

            $table->boolean('carousel_autoplay')->default(true)->after('items_columns');
            $table->unsignedSmallInteger('carousel_autoplay_delay')->default(5)->after('carousel_autoplay');
            $table->boolean('carousel_loop')->default(true)->after('carousel_autoplay_delay');
            $table->boolean('carousel_arrows')->default(true)->after('carousel_loop');
            $table->boolean('carousel_dots')->default(true)->after('carousel_arrows');
        });
    }

    public function down(): void
    {
        Schema::table('content_sections', function (Blueprint $table) {
            $table->dropColumn([
                'layout',
                'items',
                'items_columns',
                'carousel_autoplay',
                'carousel_autoplay_delay',
                'carousel_loop',
                'carousel_arrows',
                'carousel_dots',
            ]);
        });
    }
};
