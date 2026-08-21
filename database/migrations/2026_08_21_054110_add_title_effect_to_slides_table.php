<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Warna & animasi judul kini milik tiap slide, bukan satu pengaturan bersama —
 * slide pembuka boleh diketik huruf demi huruf sementara slide berikutnya
 * cukup disorot stabilo. Semuanya muat dalam satu kolom JSON yang dibaca
 * `App\Support\HeroTitleEffect::fromArray()`; slide lama bernilai null dan
 * jatuh ke bawaannya, yaitu tanpa efek.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('slides', function (Blueprint $table): void {
            $table->json('title_effect')->nullable()->after('subtitle');
        });
    }

    public function down(): void
    {
        Schema::table('slides', function (Blueprint $table): void {
            $table->dropColumn('title_effect');
        });
    }
};
