<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Migrasi yang memindahkan kolom `content` lama menjadi blok teks pertama.
 * Dijalankan ulang di sini terhadap baris yang sengaja dibuat "gaya lama".
 */
class ContentToBlocksMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_existing_content_becomes_the_first_text_block(): void
    {
        DB::table('static_pages')->insert([
            'title' => 'Profil',
            'slug' => 'profil-lama',
            'content' => '<p>Konten lama halaman.</p>',
            'blocks' => json_encode([
                ['type' => 'image_cover', 'image' => 'pages/blocks/foto.jpg'],
            ]),
            'is_active' => true,
            'sort_order' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->runContentMigration();

        $page = DB::table('static_pages')->where('slug', 'profil-lama')->first();
        $blocks = json_decode($page->blocks, true);

        $this->assertNull($page->content);
        $this->assertSame('rich_text', $blocks[0]['type']);
        $this->assertSame('<p>Konten lama halaman.</p>', $blocks[0]['content']);
        $this->assertSame('image_cover', $blocks[1]['type'], 'Blok lama tetap di urutan berikutnya.');
    }

    public function test_pages_without_content_are_left_alone(): void
    {
        DB::table('static_pages')->insert([
            'title' => 'Kosong',
            'slug' => 'kosong',
            'content' => null,
            'blocks' => json_encode([]),
            'is_active' => true,
            'sort_order' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->runContentMigration();

        $page = DB::table('static_pages')->where('slug', 'kosong')->first();

        $this->assertSame([], json_decode($page->blocks, true));
    }

    private function runContentMigration(): void
    {
        require_once database_path('migrations/2026_08_06_094411_move_content_into_blocks_on_static_pages_and_programs_tables.php');

        (require database_path('migrations/2026_08_06_094411_move_content_into_blocks_on_static_pages_and_programs_tables.php'))->up();
    }
}
