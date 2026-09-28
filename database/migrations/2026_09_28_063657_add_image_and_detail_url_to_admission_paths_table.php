<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Gambar sampul & tautan "Lihat Selengkapnya" untuk kartu jalur di halaman
     * depan PPDB. Keduanya opsional — kartu tanpa gambar memakai ikonnya.
     */
    public function up(): void
    {
        Schema::table('admission_paths', function (Blueprint $table): void {
            $table->string('image')->nullable()->after('icon_image');
            $table->string('detail_url', 500)->nullable()->after('description');
        });
    }

    public function down(): void
    {
        Schema::table('admission_paths', function (Blueprint $table): void {
            $table->dropColumn(['image', 'detail_url']);
        });
    }
};
