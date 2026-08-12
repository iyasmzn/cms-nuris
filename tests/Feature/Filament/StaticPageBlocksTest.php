<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\StaticPages\Pages\EditStaticPage;
use App\Models\Media;
use App\Models\StaticPage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Blok "Konten Tambahan" pada form halaman statis: pilihan gambar tiap blok
 * harus tersimpan sebagai path bersih, tanpa kunci bantu milik image picker.
 */
class StaticPageBlocksTest extends TestCase
{
    use RefreshDatabase;

    public function test_card_and_media_blocks_are_saved_with_resolved_images(): void
    {
        $media = Media::factory()->create(['path' => 'media/asrama.jpg']);
        $page = StaticPage::factory()->create(['blocks' => []]);

        Livewire::actingAs($this->panelUser('StaticPage'))
            ->test(EditStaticPage::class, ['record' => $page->id])
            ->fillForm([
                'blocks' => [
                    [
                        'type' => 'rich_text',
                        'content' => '<p>Paragraf pembuka.</p>',
                    ],
                    [
                        'type' => 'media_text',
                        'media_image_source' => 'library',
                        'media_image_library' => $media->id,
                        'media_position' => 'left',
                        'heading' => 'Asrama',
                        'text' => '<p>Kamar luas.</p>',
                    ],
                    [
                        'type' => 'cards',
                        'items_columns' => 2,
                        'items_ratio' => '16-9',
                        'items' => [
                            [
                                'title' => 'Tahfidz',
                                'description' => 'Setoran harian.',
                                'image_source' => 'library',
                                'image_library' => $media->id,
                            ],
                        ],
                    ],
                ],
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $blocks = $page->refresh()->blocks;

        $this->assertSame('<p>Paragraf pembuka.</p>', $blocks[0]['content']);

        $this->assertSame('media/asrama.jpg', $blocks[1]['media_image']);
        $this->assertArrayNotHasKey('media_image_source', $blocks[1]);
        $this->assertArrayNotHasKey('media_image_library', $blocks[1]);

        $this->assertSame('media/asrama.jpg', $blocks[2]['items'][0]['image']);
        $this->assertArrayNotHasKey('image_source', $blocks[2]['items'][0]);
        $this->assertArrayNotHasKey('image_library', $blocks[2]['items'][0]);
        $this->assertSame('Tahfidz', $blocks[2]['items'][0]['title']);
        $this->assertSame('16-9', $blocks[2]['items_ratio']);
    }
}
