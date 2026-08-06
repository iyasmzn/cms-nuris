<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Konten utama halaman dan program kini disusun sebagai blok. Isi kolom
 * `content` yang lama dipindahkan menjadi blok teks pertama sehingga tampilan
 * publiknya tetap sama, lalu kolomnya dikosongkan.
 *
 * Kolom `content` sengaja tidak ikut dihapus: bentuknya masih dipakai sebagai
 * jalur balik migrasi ini bila perlu dikembalikan.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['static_pages', 'programs'] as $table) {
            DB::table($table)
                ->whereNotNull('content')
                ->where('content', '!=', '')
                ->orderBy('id')
                ->each(function (object $record) use ($table): void {
                    $blocks = json_decode($record->blocks ?? '[]', true);
                    $blocks = is_array($blocks) ? $blocks : [];

                    array_unshift($blocks, [
                        'type' => 'rich_text',
                        'content' => $record->content,
                    ]);

                    DB::table($table)
                        ->where('id', $record->id)
                        ->update([
                            'blocks' => json_encode($blocks),
                            'content' => null,
                        ]);
                });
        }
    }

    public function down(): void
    {
        foreach (['static_pages', 'programs'] as $table) {
            DB::table($table)
                ->orderBy('id')
                ->each(function (object $record) use ($table): void {
                    $blocks = json_decode($record->blocks ?? '[]', true);

                    if (! is_array($blocks) || ($blocks[0]['type'] ?? null) !== 'rich_text') {
                        return;
                    }

                    $first = array_shift($blocks);

                    DB::table($table)
                        ->where('id', $record->id)
                        ->update([
                            'blocks' => json_encode(array_values($blocks)),
                            'content' => $first['content'] ?? null,
                        ]);
                });
        }
    }
};
