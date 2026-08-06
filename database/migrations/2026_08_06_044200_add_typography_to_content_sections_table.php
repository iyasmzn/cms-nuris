<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Deskripsi tidak lagi wajib — seksi kartu sering cukup dengan judulnya
     * saja. Gaya huruf tiap elemen teks (label, judul, deskripsi, judul &
     * deskripsi kartu) disimpan sebagai satu kolom JSON karena bentuknya sama
     * untuk semua elemen dan hanya dibaca oleh seksinya sendiri.
     */
    public function up(): void
    {
        Schema::table('content_sections', function (Blueprint $table) {
            $table->text('description')->nullable()->change();
            $table->json('typography')->nullable()->after('description');
        });
    }

    public function down(): void
    {
        Schema::table('content_sections', function (Blueprint $table) {
            $table->dropColumn('typography');
            $table->text('description')->nullable(false)->change();
        });
    }
};
