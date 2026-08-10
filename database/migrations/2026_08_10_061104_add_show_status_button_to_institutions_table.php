<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tidak semua jenjang memakai cek status & pembayaran bawaan sistem — jenjang
 * bermode eksternal/embed misalnya mengurusnya di situs lain. Tombolnya kini
 * bisa disembunyikan per jenjang, tetap menyala secara bawaan.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('institutions', function (Blueprint $table): void {
            $table->boolean('show_status_button')->default(true)->after('closed_message');
        });
    }

    public function down(): void
    {
        Schema::table('institutions', function (Blueprint $table): void {
            $table->dropColumn('show_status_button');
        });
    }
};
