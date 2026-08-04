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

        $this->assertMatchesRegularExpression(
            '/<section id="'.preg_quote($anchorId, '/').'"[^>]*>/',
            $html,
        );

        preg_match('/<section id="'.preg_quote($anchorId, '/').'"[^>]*>/', $html, $matches);

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
            'background_image' => 'content-sections/backgrounds/diam.jpg',
            'background_blur' => 8,
            'background_overlay' => 45,
            'is_published' => true,
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('class="content-section-bg-fixed"', false)
            ->assertSee('content-section-clip"', false)
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
            'is_published' => true,
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('translate3d', false)
            ->assertDontSee('class="content-section-bg-fixed"', false);

        $section->update(['background_parallax_mode' => 'fixed']);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('class="content-section-bg-fixed"', false)
            ->assertDontSee('translate3d', false);

        $section->update(['background_parallax_mode' => 'none']);

        $this->get(route('home'))
            ->assertOk()
            ->assertDontSee('class="content-section-bg-fixed"', false)
            ->assertDontSee('translate3d', false)
            ->assertDontSee('content-section-clip"', false);
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
