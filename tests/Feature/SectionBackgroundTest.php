<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Support\SectionBackground;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Latar seksi bawaan halaman depan — sama fiturnya dengan seksi dinamis, hanya
 * nilainya disimpan sebagai setting. Seksi Statistik (id="profil") dipakai
 * sebagai contoh karena selalu ikut dirender.
 */
class SectionBackgroundTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_section_keeps_its_own_background_when_left_on_default(): void
    {
        $this->assertStringContainsString('background:transparent', $this->sectionTag('profil'));

        $this->get(route('home'))
            ->assertOk()
            ->assertDontSee('section-bg-layer"', false);
    }

    public function test_the_plain_background_choices_map_to_the_tokens_their_labels_promise(): void
    {
        Setting::setMany(['section_stats_background' => 'base']);

        // "Abu Lembut" = --bg (#f5f5f7), warna dasar halaman
        $this->assertStringContainsString('background:var(--bg)', $this->sectionTag('profil'));

        Setting::setMany(['section_stats_background' => 'alt']);

        // "Putih Bersih" = --bg-alt (#ffffff)
        $this->assertStringContainsString('background:var(--bg-alt, var(--bg))', $this->sectionTag('profil'));
    }

    public function test_a_built_in_section_can_be_decorated_with_an_svg_pattern(): void
    {
        Setting::setMany([
            'section_stats_background' => 'alt',
            'section_stats_background_pattern' => 'dots',
            'section_stats_background_pattern_opacity' => 14,
            'section_stats_background_pattern_scale' => 75,
        ]);

        $html = $this->get(route('home'))->assertOk()->getContent();

        $this->assertStringContainsString('background:transparent', $this->sectionTag('profil'));
        $this->assertStringContainsString('class="section-bg-layer section-bg-layer-pattern"', $html);
        $this->assertStringContainsString('background-color:var(--bg-alt, var(--bg))', $html);
        $this->assertStringContainsString('opacity:0.14', $html);
        // Ubin dots 24×24 pada skala 75% jadi 18×18
        $this->assertStringContainsString('--section-pattern-size:18px 18px', $html);
    }

    public function test_a_built_in_section_pattern_can_be_animated(): void
    {
        Setting::setMany([
            'section_stats_background' => 'base',
            'section_stats_background_pattern' => 'dots',
            'section_stats_background_pattern_animated' => true,
            'section_stats_background_pattern_motion' => 'drift',
            'section_stats_background_pattern_speed' => 12,
        ]);

        $html = $this->get(route('home'))->assertOk()->getContent();

        $this->assertStringContainsString('class="section-bg-pattern section-bg-pattern-moving section-bg-pattern-drift"', $html);
        // Ubin 24px pada laju 12 px/detik = 2 detik per putaran
        $this->assertStringContainsString('--section-pattern-duration:2s', $html);
    }

    /**
     * Seksi yang dibiarkan "Bawaan Seksi" tetap memakai latar rancangannya
     * sendiri; pola dilukis di atasnya, bukan menggantikannya.
     */
    public function test_a_pattern_keeps_the_sections_own_designed_background(): void
    {
        Setting::setMany([
            'section_stats_background' => 'default',
            'section_stats_background_pattern' => 'grid',
        ]);

        $html = $this->get(route('home'))->assertOk()->getContent();

        $this->assertStringContainsString('class="section-bg-layer section-bg-layer-pattern"', $html);
        $this->assertStringContainsString('background:transparent', $this->sectionTag('profil'));
    }

    public function test_it_renders_a_background_image_with_blur_overlay_and_parallax(): void
    {
        $this->configureStatsBackground([
            'section_stats_background_blur' => 8,
            'section_stats_background_overlay' => 45,
            'section_stats_background_parallax_mode' => 'scroll',
            'section_stats_background_parallax_speed' => 50,
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('storage/sections/backgrounds/latar.jpg', false)
            ->assertSee('filter:blur(8px)', false)
            ->assertSee('rgba(17,24,39,0.45)', false)
            ->assertSee('translate3d', false)
            ->assertSee('scale: 1.5', false)
            ->assertSee('amplitude: 0.25', false);

        $this->assertStringContainsString('section-light', $this->sectionTag('profil'));
    }

    public function test_a_fixed_background_is_locked_to_the_viewport_instead_of_scrolling(): void
    {
        $this->configureStatsBackground([
            'section_stats_background_overlay' => 65,
            'section_stats_background_parallax_mode' => 'fixed',
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('class="section-bg-fixed"', false)
            ->assertSee('rgba(17,24,39,0.65)', false)
            // Mode diam tidak memakai penggeser Alpine sama sekali
            ->assertDontSee('translate3d', false);

        $this->assertStringContainsString('section-clip', $this->sectionTag('profil'));
    }

    public function test_light_text_can_be_turned_off_for_a_bright_background_image(): void
    {
        $this->configureStatsBackground(['section_stats_background_light_text' => false]);

        $this->assertStringNotContainsString('section-light', $this->sectionTag('profil'));
    }

    public function test_effects_are_skipped_when_the_image_is_missing(): void
    {
        Setting::setMany([
            'section_stats_background' => 'image',
            'section_stats_background_image' => '',
            'section_stats_background_overlay' => 45,
            'section_stats_background_parallax_mode' => 'scroll',
        ]);

        $tag = $this->sectionTag('profil');

        $this->assertStringContainsString('background:transparent', $tag);
        $this->assertStringNotContainsString('section-light', $tag);

        $this->get(route('home'))
            ->assertOk()
            ->assertDontSee('rgba(17,24,39,', false)
            ->assertDontSee('translate3d', false);
    }

    public function test_the_hero_section_ignores_background_settings(): void
    {
        Setting::setMany([
            'section_hero_background' => 'image',
            'section_hero_background_image' => 'sections/backgrounds/hero.jpg',
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertDontSee('storage/sections/backgrounds/hero.jpg', false);
    }

    public function test_each_wrapped_section_reads_its_own_setting_key(): void
    {
        Setting::setMany([
            'section_programs_background' => 'image',
            'section_programs_background_image' => 'sections/backgrounds/program.jpg',
        ]);

        // Latar seksi Program terpasang, seksi lain tidak ikut berubah
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('storage/sections/backgrounds/program.jpg', false);

        $this->assertStringContainsString('background:transparent', $this->sectionTag('profil'));
    }

    public function test_helpers_ignore_effects_without_an_image(): void
    {
        $background = new SectionBackground(
            mode: 'image',
            image: null,
            overlay: 45,
            parallaxMode: 'scroll',
            lightText: true,
        );

        $this->assertFalse($background->hasImage());
        $this->assertFalse($background->usesScrollParallax());
        $this->assertFalse($background->usesFixedBackground());
        $this->assertFalse($background->usesLightText());
        $this->assertNull($background->imageUrl());
        $this->assertSame(0, $background->blurRadius());
        $this->assertSame(0.45, $background->overlayOpacity());
        $this->assertSame('var(--bg)', $background->style());
        $this->assertSame('transparent', $background->style('transparent'));
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function configureStatsBackground(array $overrides = []): void
    {
        Setting::setMany([
            'section_stats_background' => 'image',
            'section_stats_background_image' => 'sections/backgrounds/latar.jpg',
            ...$overrides,
        ]);
    }

    /**
     * Tag <section> pembuka milik seksi tertentu, agar assertion gaya tidak
     * tertukar dengan seksi lain di halaman depan.
     */
    private function sectionTag(string $id): string
    {
        $html = $this->get(route('home'))->assertOk()->getContent();

        $pattern = '/<section [^>]*id="'.preg_quote($id, '/').'"[^>]*>/';

        $this->assertMatchesRegularExpression($pattern, $html);

        preg_match($pattern, $html, $matches);

        return $matches[0];
    }
}
