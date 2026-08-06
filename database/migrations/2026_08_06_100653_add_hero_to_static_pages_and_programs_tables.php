<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cover hero halaman & program: gambar, berkas video, atau video YouTube —
 * pilihan yang sama dengan slide hero halaman depan. Disimpan sebagai satu
 * kolom JSON karena bentuknya sekelompok pengaturan, bukan data yang dicari.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['static_pages', 'programs'] as $table) {
            Schema::table($table, function (Blueprint $table): void {
                $table->json('hero')->nullable()->after('blocks');
            });
        }
    }

    public function down(): void
    {
        foreach (['static_pages', 'programs'] as $table) {
            Schema::table($table, function (Blueprint $table): void {
                $table->dropColumn('hero');
            });
        }
    }
};
