<?php

namespace Tests\Feature;

use App\Models\ContentSection;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContentSectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_shows_a_published_section_with_its_cta_on_the_home_page(): void
    {
        ContentSection::factory()->create([
            'eyebrow' => 'Tentang Kami',
            'title' => 'Lingkungan Belajar yang Nyaman',
            'description' => '<p>Kurikulum nasional dipadukan dengan diniyah.</p>',
            'cta_label' => 'Kenali Kami',
            'cta_url' => '/profil',
            'is_published' => true,
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Tentang Kami')
            ->assertSee('Lingkungan Belajar yang Nyaman')
            ->assertSee('Kurikulum nasional dipadukan dengan diniyah.', false)
            ->assertSee('Kenali Kami')
            ->assertSee('href="/profil"', false);
    }

    public function test_it_hides_an_unpublished_section(): void
    {
        ContentSection::factory()->create([
            'title' => 'Seksi Tersembunyi',
            'is_published' => false,
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertDontSee('Seksi Tersembunyi');
    }

    public function test_it_omits_the_cta_button_when_the_link_is_empty(): void
    {
        ContentSection::factory()->withoutCta()->create([
            'title' => 'Seksi Tanpa Tombol',
            'is_published' => true,
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Seksi Tanpa Tombol')
            ->assertDontSee('Selengkapnya');
    }

    public function test_the_image_position_controls_the_column_order(): void
    {
        $left = ContentSection::factory()->create([
            'title' => 'Gambar Kiri',
            'image' => 'content-sections/kiri.jpg',
            'image_position' => 'left',
            'is_published' => true,
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('lg:order-1', false)
            ->assertSee('data-aos="fade-right"', false);

        $left->update(['image_position' => 'right']);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('data-aos="fade-left"', false);
    }

    public function test_the_background_choices_map_to_the_tokens_their_labels_promise(): void
    {
        $section = ContentSection::factory()->create([
            'anchor' => 'latar-uji',
            'background' => 'default',
            'is_published' => true,
        ]);

        // "Abu Lembut" = --bg (#f5f5f7), warna dasar halaman
        $this->assertStringContainsString(
            'background:var(--bg)',
            $this->sectionTag('latar-uji'),
        );

        $section->update(['background' => 'alt']);

        // "Putih Bersih" = --bg-alt (#ffffff)
        $this->assertStringContainsString(
            'background:var(--bg-alt, var(--bg))',
            $this->sectionTag('latar-uji'),
        );
    }

    /**
     * Tag <section> pembuka milik seksi dengan anchor tertentu, agar assertion
     * gaya tidak tertukar dengan seksi lain di halaman depan.
     */
    private function sectionTag(string $anchorId): string
    {
        $html = $this->get(route('home'))->assertOk()->getContent();

        $pattern = '/<section [^>]*id="'.preg_quote($anchorId, '/').'"[^>]*>/';

        $this->assertMatchesRegularExpression($pattern, $html);

        preg_match($pattern, $html, $matches);

        return $matches[0];
    }

    public function test_it_renders_a_background_image_with_blur_overlay_and_parallax(): void
    {
        ContentSection::factory()->withBackgroundImage()->create([
            'title' => 'Seksi Berlatar Gambar',
            'background_image' => 'content-sections/backgrounds/latar.jpg',
            'background_blur' => 8,
            'background_overlay' => 45,
            'background_parallax_mode' => 'scroll',
            'background_parallax_speed' => 50,
            'is_published' => true,
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('storage/content-sections/backgrounds/latar.jpg', false)
            ->assertSee('filter:blur(8px)', false)
            ->assertSee('rgba(17,24,39,0.45)', false)
            ->assertSee('translate3d', false)
            ->assertSee('scale: 1.5', false)
            ->assertSee('amplitude: 0.25', false)
            ->assertSee('style="color:#ffffff"', false);
    }

    public function test_the_parallax_strength_scales_with_the_configured_speed(): void
    {
        $section = ContentSection::factory()->withBackgroundImage()->create([
            'title' => 'Parallax Maksimal',
            'background_parallax_speed' => 100,
            'is_published' => true,
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('scale: 2', false)
            ->assertSee('amplitude: 0.5', false);

        $section->update(['background_parallax_speed' => 10]);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('scale: 1.1', false)
            ->assertSee('amplitude: 0.05', false);
    }

    public function test_a_fixed_background_is_locked_to_the_viewport_instead_of_scrolling(): void
    {
        ContentSection::factory()->withFixedBackground()->create([
            'title' => 'Latar Terkunci',
            'anchor' => 'latar-diam',
            'background_image' => 'content-sections/backgrounds/diam.jpg',
            'background_blur' => 8,
            'background_overlay' => 45,
            'is_published' => true,
        ]);

        $this->assertStringContainsString('section-clip', $this->sectionTag('latar-diam'));

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('class="section-bg-fixed"', false)
            ->assertSee('storage/content-sections/backgrounds/diam.jpg', false)
            ->assertSee('filter:blur(8px)', false)
            ->assertSee('rgba(17,24,39,0.45)', false)
            // Mode diam tidak memakai penggeser Alpine sama sekali
            ->assertDontSee('translate3d', false);
    }

    public function test_switching_between_parallax_modes_swaps_the_background_layer(): void
    {
        $section = ContentSection::factory()->withBackgroundImage()->create([
            'title' => 'Ganti Mode',
            'anchor' => 'ganti-mode',
            'is_published' => true,
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('translate3d', false)
            ->assertDontSee('class="section-bg-fixed"', false);

        $section->update(['background_parallax_mode' => 'fixed']);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('class="section-bg-fixed"', false)
            ->assertDontSee('translate3d', false);

        $section->update(['background_parallax_mode' => 'none']);

        $this->get(route('home'))
            ->assertOk()
            ->assertDontSee('class="section-bg-fixed"', false)
            ->assertDontSee('translate3d', false);

        $this->assertStringNotContainsString('section-clip', $this->sectionTag('ganti-mode'));
    }

    public function test_background_effects_are_skipped_when_the_background_is_not_an_image(): void
    {
        ContentSection::factory()->create([
            'title' => 'Seksi Latar Polos',
            'background' => 'default',
            'background_image' => 'content-sections/backgrounds/latar.jpg',
            'background_blur' => 16,
            'background_overlay' => 80,
            'background_parallax_mode' => 'scroll',
            'is_published' => true,
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Seksi Latar Polos')
            ->assertDontSee('content-sections/backgrounds/latar.jpg', false)
            ->assertDontSee('translate3d', false)
            ->assertDontSee('rgba(17,24,39,0.8)', false);
    }

    public function test_a_background_image_without_effects_renders_no_overlay_or_parallax(): void
    {
        ContentSection::factory()->withBackgroundImage()->create([
            'title' => 'Latar Bersih',
            'background_blur' => 0,
            'background_overlay' => 0,
            'background_parallax_mode' => 'none',
            'background_light_text' => false,
            'is_published' => true,
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Latar Bersih')
            // Spasi di depan membedakannya dari `backdrop-filter:blur` milik navbar/popup
            ->assertDontSee(' filter:blur(', false)
            ->assertDontSee('rgba(17,24,39,', false)
            ->assertDontSee('translate3d', false)
            ->assertDontSee('style="color:#ffffff"', false);
    }

    public function test_background_helpers_ignore_effects_without_an_image(): void
    {
        $section = ContentSection::factory()->create([
            'background' => 'image',
            'background_image' => null,
            'background_overlay' => 45,
            'background_parallax_mode' => 'scroll',
        ]);

        $this->assertFalse($section->has_background_image);
        $this->assertFalse($section->uses_scroll_parallax);
        $this->assertFalse($section->uses_fixed_background);
        $this->assertFalse($section->uses_light_text);
        $this->assertNull($section->background_image_url);
        $this->assertSame(0.45, $section->overlay_opacity);
        $this->assertSame(0.3, $section->parallax_factor);
    }

    public function test_a_section_without_an_image_still_renders_its_text(): void
    {
        ContentSection::factory()->create([
            'title' => 'Seksi Tanpa Gambar',
            'image' => null,
            'is_published' => true,
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Seksi Tanpa Gambar');
    }

    // ── Tata letak kartu & carousel ───────────────────────────────

    public function test_the_cards_layout_renders_every_card_in_a_grid(): void
    {
        ContentSection::factory()->withCards(3)->create([
            'title' => 'Program Kami',
            'items_columns' => 4,
            'is_published' => true,
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Program Kami')
            ->assertSee('Kartu 1')
            ->assertSee('Kartu 2')
            ->assertSee('Kartu 3')
            ->assertSee('class="cs-grid"', false)
            ->assertSee('--cs-cols-lg:4', false)
            // Layar sedang dibatasi dua kartu meski admin memilih empat
            ->assertSee('--cs-cols-sm:2', false)
            ->assertDontSee('class="cs-carousel"', false);
    }

    public function test_a_card_shows_its_own_cta_button(): void
    {
        ContentSection::factory()->withCards(1)->create([
            'title' => 'Kartu Bertombol',
            'is_published' => true,
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('class="cs-card-cta"', false)
            ->assertSee('href="/kartu-1"', false)
            ->assertSee('Selengkapnya');
    }

    public function test_a_card_with_a_link_but_no_button_label_becomes_clickable_as_a_whole(): void
    {
        ContentSection::factory()->create([
            'title' => 'Kartu Diklik',
            'layout' => 'cards',
            'items' => [
                ['title' => 'Tanpa Tombol', 'cta_url' => '/tujuan', 'cta_new_tab' => true],
            ],
            'is_published' => true,
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('class="cs-card-link"', false)
            ->assertSee('href="/tujuan"', false)
            ->assertSee('target="_blank" rel="noopener noreferrer"', false)
            ->assertDontSee('class="cs-card-cta"', false);
    }

    public function test_the_carousel_layout_carries_its_autoplay_settings(): void
    {
        ContentSection::factory()->withCarousel(6)->create([
            'title' => 'Kartu Berjalan',
            'items_columns' => 3,
            'carousel_autoplay' => true,
            'carousel_autoplay_delay' => 8,
            'carousel_loop' => false,
            'is_published' => true,
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('class="cs-carousel"', false)
            ->assertSee('autoplay: true', false)
            ->assertSee('delay: 8000', false)
            ->assertSee('loop: false', false)
            ->assertSee('class="cs-arrow cs-arrow-prev"', false)
            ->assertSee('class="cs-dots"', false)
            ->assertDontSee('class="cs-grid"', false);
    }

    public function test_the_carousel_navigation_can_be_switched_off(): void
    {
        ContentSection::factory()->withCarousel(4)->create([
            'title' => 'Carousel Polos',
            'carousel_autoplay' => false,
            'carousel_arrows' => false,
            'carousel_dots' => false,
            'is_published' => true,
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('autoplay: false', false)
            ->assertDontSee('class="cs-arrow cs-arrow-prev"', false)
            ->assertDontSee('class="cs-dots"', false);
    }

    public function test_the_media_image_is_skipped_on_a_card_layout(): void
    {
        ContentSection::factory()->withCards(2)->create([
            'title' => 'Hanya Kartu',
            'image' => 'content-sections/gambar-utama.jpg',
            'is_published' => true,
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Hanya Kartu')
            ->assertDontSee('content-sections/gambar-utama.jpg', false);
    }

    public function test_a_card_layout_without_cards_still_renders_its_text(): void
    {
        $section = ContentSection::factory()->create([
            'title' => 'Kartu Belum Diisi',
            'layout' => 'cards',
            'items' => null,
            'is_published' => true,
        ]);

        $this->assertFalse($section->uses_cards);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Kartu Belum Diisi')
            ->assertDontSee('class="cs-grid"', false);
    }

    public function test_card_helpers_normalise_the_stored_items(): void
    {
        $section = ContentSection::factory()->make([
            'layout' => 'carousel',
            'items_columns' => 9,
            'carousel_autoplay_delay' => 99,
            'items' => [
                ['title' => 'Berjudul', 'image' => 'kartu/satu.jpg', 'cta_label' => 'Buka', 'cta_url' => '/satu'],
                ['title' => '', 'image' => null, 'description' => 'Tanpa judul & gambar — dilewati'],
                ['title' => 'Bisa Diklik', 'cta_url' => '/dua'],
            ],
        ]);

        $cards = $section->cards;

        $this->assertCount(2, $cards);
        $this->assertTrue($cards[0]->has_cta);
        $this->assertFalse($cards[0]->is_clickable);
        $this->assertStringEndsWith('/storage/kartu/satu.jpg', $cards[0]->image_url);
        $this->assertFalse($cards[1]->has_cta);
        $this->assertTrue($cards[1]->is_clickable);
        $this->assertNull($cards[1]->image_url);

        // Nilai di luar rentang dijepit ke batas yang masuk akal
        $this->assertSame(4, $section->card_columns);
        $this->assertSame(15000, $section->autoplay_delay_ms);
        $this->assertTrue($section->uses_carousel);
        $this->assertFalse($section->shows_media);
    }

    public function test_the_section_order_setting_controls_where_it_renders(): void
    {
        ContentSection::factory()->create([
            'title' => 'Seksi Paling Atas',
            'is_published' => true,
        ]);

        $section = ContentSection::first();

        Setting::set('section_order', json_encode([
            ['key' => $section->order_key, 'visible' => true],
            ['key' => 'section_faq', 'visible' => true],
        ]));

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Seksi Paling Atas');
    }

    public function test_it_still_renders_when_missing_from_a_saved_section_order(): void
    {
        ContentSection::factory()->create([
            'title' => 'Seksi Baru Dibuat',
            'is_published' => true,
        ]);

        Setting::set('section_order', json_encode([
            ['key' => 'section_faq', 'visible' => true],
        ]));

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Seksi Baru Dibuat');
    }

    public function test_a_stale_order_entry_for_a_deleted_section_is_ignored(): void
    {
        Setting::set('section_order', json_encode([
            ['key' => ContentSection::ORDER_KEY_PREFIX.'999', 'visible' => true],
            ['key' => 'section_faq', 'visible' => true],
        ]));

        $this->get(route('home'))->assertOk();
    }

    public function test_the_anchor_id_falls_back_to_the_record_id(): void
    {
        $section = ContentSection::factory()->create([
            'anchor' => null,
            'is_published' => true,
        ]);

        $this->assertSame('seksi-'.$section->id, $section->anchor_id);

        $section->update(['anchor' => 'Fasilitas Kami']);

        $this->assertSame('fasilitas-kami', $section->fresh()->anchor_id);
    }

    public function test_published_scope_returns_only_published_in_sort_order(): void
    {
        $second = ContentSection::factory()->create(['is_published' => true, 'sort_order' => 2]);
        $first = ContentSection::factory()->create(['is_published' => true, 'sort_order' => 1]);
        ContentSection::factory()->create(['is_published' => false, 'sort_order' => 0]);

        $published = ContentSection::published()->get();

        $this->assertCount(2, $published);
        $this->assertSame([$first->id, $second->id], $published->pluck('id')->all());
    }

    public function test_order_key_round_trips(): void
    {
        $section = ContentSection::factory()->create();

        $this->assertSame($section->id, ContentSection::idFromOrderKey($section->order_key));
        $this->assertNull(ContentSection::idFromOrderKey('section_faq'));
        $this->assertNull(ContentSection::idFromOrderKey(ContentSection::ORDER_KEY_PREFIX.'abc'));
    }
}
