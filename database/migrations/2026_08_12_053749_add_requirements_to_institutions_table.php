<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Persyaratan dokumen sebelumnya tertanam di blade halaman PPDB sehingga sama
 * untuk semua jenjang. Kini daftarnya bisa diisi per jenjang (kosong = ikut
 * pengaturan global) dan ditampilkan/disembunyikan per jenjang.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('institutions', function (Blueprint $table): void {
            $table->json('requirements')->nullable()->after('fees');
            $table->boolean('show_requirements')->default(true)->after('show_status_button');
        });
    }

    public function down(): void
    {
        Schema::table('institutions', function (Blueprint $table): void {
            $table->dropColumn(['requirements', 'show_requirements']);
        });
    }
};
