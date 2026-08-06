<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Halaman dan program kini disusun dari blok seksi selebar layar. Sidebar
 * kanannya jadi opsional: mati secara bawaan agar seksinya bisa penuh, dan bila
 * dinyalakan seksinya mengecil jadi kartu bersudut membulat.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['static_pages', 'programs'] as $table) {
            Schema::table($table, function (Blueprint $table): void {
                $table->boolean('show_sidebar')->default(false)->after('blocks');
            });
        }
    }

    public function down(): void
    {
        foreach (['static_pages', 'programs'] as $table) {
            Schema::table($table, function (Blueprint $table): void {
                $table->dropColumn('show_sidebar');
            });
        }
    }
};
