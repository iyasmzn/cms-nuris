<?php

namespace Tests\Feature;

use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Pita kepala halaman: satu bentuk membulat dan satu setting pola di Tema &
 * Tampilan yang berlaku untuk seluruh pita di situs, sebab pita kepala adalah
 * elemen rancangan yang sama di semua halaman.
 */
class HeroPatternTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_bands_carry_the_eight_pointed_star_until_told_otherwise(): void
    {
        $html = $this->get(route('programs.index'))->assertOk()->getContent();

        $this->assertStringContainsString('class="hero-pattern"', $html);
        // Ubin Khatam 44×44 pada skala bawaan 150% jadi 66×66
        $this->assertStringContainsString('--hero-pattern-size:66px 66px', $html);
        $this->assertStringContainsString('--hero-pattern-color:#ffffff', $html);
        $this->assertStringContainsString('--hero-pattern-opacity:0.08', $html);
    }

    public function test_one_setting_reaches_every_band(): void
    {
        $this->choose('diamonds');

        // Satu contoh dari tiap bentuk pita: hero berhias, pita detail, dan
        // layar auth yang memakai pita sebagai latar penuh.
        foreach ([route('programs.index'), route('ppdb.status'), route('login')] as $url) {
            $this->get($url)
                ->assertOk()
                ->assertSee('page-band', false)
                ->assertSee('class="hero-pattern"', false);
        }
    }

    public function test_choosing_no_pattern_leaves_the_bands_plain(): void
    {
        $this->choose('none');

        $this->get(route('programs.index'))
            ->assertOk()
            ->assertSee('page-band', false)
            ->assertDontSee('class="hero-pattern"', false);
    }

    public function test_an_unknown_pattern_leaves_the_bands_plain_instead_of_failing(): void
    {
        $this->choose('pola-yang-sudah-dihapus');

        $this->get(route('programs.index'))
            ->assertOk()
            ->assertDontSee('class="hero-pattern"', false);
    }

    public function test_the_tile_size_opacity_and_colour_follow_the_setting(): void
    {
        $this->choose('diamonds', opacity: 14, scale: 150, color: 'gold');

        $html = $this->get(route('programs.index'))->assertOk()->getContent();

        // Ubin belah ketupat 32×32 pada skala 150% jadi 48×48
        $this->assertStringContainsString('--hero-pattern-size:48px 48px', $html);
        $this->assertStringContainsString('--hero-pattern-opacity:0.14', $html);
        $this->assertStringContainsString('--hero-pattern-color:#fbbf24', $html);
    }

    public function test_a_custom_hex_is_used_only_when_it_is_really_a_hex(): void
    {
        $this->choose('dots', color: 'custom');
        Setting::setMany(['hero_pattern_custom_color' => '#ffcc00']);

        $this->assertStringContainsString(
            '--hero-pattern-color:#ffcc00',
            $this->get(route('programs.index'))->assertOk()->getContent(),
        );

        Setting::setMany(['hero_pattern_custom_color' => 'url(jahat.svg);color:red']);

        $html = $this->get(route('programs.index'))->assertOk()->getContent();

        $this->assertStringContainsString('--hero-pattern-color:#ffffff', $html);
        $this->assertStringNotContainsString('jahat.svg', $html);
    }

    /**
     * Hero beranda ikut bentuk pitanya, tapi tetap tanpa pola: foto slide di
     * baliknya berebut perhatian dengan judul bila ditimpa pola.
     */
    public function test_the_home_hero_takes_the_band_shape_but_no_pattern(): void
    {
        $this->choose('diamonds');

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('hero-media page-band', false)
            ->assertDontSee('class="hero-pattern"', false);
    }

    public function test_both_page_shells_inset_the_band_and_follow_the_theme_colour(): void
    {
        foreach ([route('home'), route('programs.index')] as $url) {
            $this->get($url)
                ->assertOk()
                ->assertSee('<body class="page-inset', false)
                ->assertSee('--hero-band: linear-gradient(135deg,', false);
        }
    }

    private function choose(
        string $pattern,
        int $opacity = 8,
        int $scale = 100,
        string $color = 'white',
    ): void {
        Setting::setMany([
            'hero_pattern' => $pattern,
            'hero_pattern_opacity' => $opacity,
            'hero_pattern_scale' => $scale,
            'hero_pattern_color' => $color,
        ]);
    }
}
