<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tautan opsional pada kartu statistik: seluruh kartunya menjadi bisa
     * diklik bila diisi, mis. menuju halaman prestasi atau profil.
     */
    public function up(): void
    {
        Schema::table('stats', function (Blueprint $table) {
            $table->string('url')->nullable()->after('sub');
        });
    }

    public function down(): void
    {
        Schema::table('stats', function (Blueprint $table) {
            $table->dropColumn('url');
        });
    }
};
