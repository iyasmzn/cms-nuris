<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Menandai field pilihan (dropdown/radio) yang jawabannya layak dijadikan
     * grafik di dasbor — misalnya "Pilihan Kelas". Tanpa penanda ini setiap
     * dropdown baru ikut membanjiri dasbor dengan grafik yang tidak diminta.
     */
    public function up(): void
    {
        Schema::table('ppdb_fields', function (Blueprint $table): void {
            $table->boolean('show_in_dashboard')->default(false)->after('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('ppdb_fields', function (Blueprint $table): void {
            $table->dropColumn('show_in_dashboard');
        });
    }
};
