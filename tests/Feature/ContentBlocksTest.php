<?php

namespace Tests\Feature;

use App\Models\StaticPage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Blok "Konten Tambahan" yang dirender di bawah konten utama halaman, artikel,
 * program, kegiatan, dan cerita.
 */
class ContentBlocksTest extends TestCase
{
    use RefreshDatabase;

    public function test_rich_text_block_is_rendered(): void
    {
        $page = StaticPage::factory()->create([
            'blocks' => [
                ['type' => 'rich_text', 'content' => '<p>Sambutan dari pengasuh.</p>'],
            ],
        ]);

        $this->get(route('page.show', $page->slug))
            ->assertOk()
            ->assertSee('Sambutan dari pengasuh.', false)
            ->assertSee('class="block-prose"', false);
    }

    public function test_empty_rich_text_block_renders_nothing(): void
    {
        $page = StaticPage::factory()->create([
            'blocks' => [
                ['type' => 'rich_text', 'content' => '<p></p>'],
            ],
        ]);

        $this->get(route('page.show', $page->slug))
            ->assertOk()
            ->assertDontSee('class="block-prose"', false);
    }

    public function test_media_text_block_renders_image_heading_and_cta(): void
    {
        $page = StaticPage::factory()->create([
            'blocks' => [
                [
                    'type' => 'media_text',
                    'media_image' => 'pages/blocks/asrama.jpg',
                    'media_position' => 'left',
                    'heading' => 'Asrama Nyaman',
                    'text' => '<p>Kamar luas dengan pengawasan musyrif.</p>',
                    'cta_label' => 'Lihat Fasilitas',
                    'cta_url' => '/fasilitas',
                    'cta_new_tab' => true,
                ],
            ],
        ]);

        $this->get(route('page.show', $page->slug))
            ->assertOk()
            ->assertSee('Asrama Nyaman')
            ->assertSee('Kamar luas dengan pengawasan musyrif.', false)
            ->assertSee('pages/blocks/asrama.jpg', false)
            ->assertSee('Lihat Fasilitas')
            ->assertSee('href="/fasilitas"', false)
            ->assertSee('target="_blank"', false);
    }

    public function test_cards_block_renders_each_card(): void
    {
        $page = StaticPage::factory()->create([
            'blocks' => [
                [
                    'type' => 'cards',
                    'items_columns' => 2,
                    'items' => [
                        ['title' => 'Tahfidz', 'description' => 'Setoran harian.', 'cta_label' => 'Detail', 'cta_url' => '/tahfidz'],
                        ['title' => 'Bahasa Arab', 'image' => 'pages/blocks/cards/arab.jpg'],
                        ['description' => 'Kartu tanpa judul dan gambar diabaikan.'],
                    ],
                ],
            ],
        ]);

        $response = $this->get(route('page.show', $page->slug))->assertOk();

        $response->assertSee('Tahfidz')
            ->assertSee('Setoran harian.')
            ->assertSee('Bahasa Arab')
            ->assertSee('pages/blocks/cards/arab.jpg', false)
            ->assertSee('--cs-cols-lg:2', false)
            ->assertSee('class="cs-grid"', false)
            ->assertDontSee('Kartu tanpa judul dan gambar diabaikan.');
    }

    public function test_carousel_cards_block_renders_track_and_settings(): void
    {
        $page = StaticPage::factory()->create([
            'blocks' => [
                [
                    'type' => 'cards_carousel',
                    'items_columns' => 3,
                    'carousel_autoplay' => true,
                    'carousel_autoplay_delay' => 7,
                    'carousel_loop' => false,
                    'carousel_arrows' => true,
                    'carousel_dots' => false,
                    'items' => [
                        ['title' => 'Kartu Satu'],
                        ['title' => 'Kartu Dua'],
                    ],
                ],
            ],
        ]);

        $this->get(route('page.show', $page->slug))
            ->assertOk()
            ->assertSee('Kartu Satu')
            ->assertSee('class="cs-track"', false)
            ->assertSee('delay: 7000', false)
            ->assertSee('loop: false', false)
            ->assertSee('pauseOnHover: true', false)
            ->assertSee('cs-arrow cs-arrow-prev', false)
            ->assertDontSee('class="cs-dots"', false);
    }

    public function test_carousel_cards_block_can_keep_running_while_hovered(): void
    {
        $page = StaticPage::factory()->create([
            'blocks' => [
                [
                    'type' => 'cards_carousel',
                    'carousel_autoplay' => true,
                    'carousel_pause_on_hover' => false,
                    'items' => [
                        ['title' => 'Kartu Satu'],
                        ['title' => 'Kartu Dua'],
                    ],
                ],
            ],
        ]);

        $this->get(route('page.show', $page->slug))
            ->assertOk()
            ->assertSee('autoplay: true', false)
            ->assertSee('pauseOnHover: false', false);
    }

    public function test_card_block_without_valid_cards_renders_nothing(): void
    {
        $page = StaticPage::factory()->create([
            'blocks' => [
                ['type' => 'cards', 'items' => []],
            ],
        ]);

        $this->get(route('page.show', $page->slug))
            ->assertOk()
            ->assertDontSee('class="cs-grid"', false);
    }
}
