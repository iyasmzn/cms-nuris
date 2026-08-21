<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\Slide;
use App\Support\HeroTitleEffect;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HeroTitleEffectTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Satu slide dengan efek judul sesuai kebutuhan tiap pengujian.
     *
     * @param  array<string, mixed>  $effect
     */
    private function slide(array $effect = [], string $title = 'Sekolah Unggulan'): Slide
    {
        return Slide::factory()->create([
            'title' => $title,
            'title_effect' => $effect,
        ]);
    }

    public function test_a_slide_carries_no_effect_by_default(): void
    {
        Slide::factory()->create(['title' => 'Sekolah Unggulan']);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('class="hero-title text-3xl', false)
            ->assertDontSee('hero-fx-', false)
            ->assertDontSee('--hero-title-duration', false);
    }

    public function test_the_chosen_effect_and_its_timing_reach_the_title(): void
    {
        $this->slide([
            'effect' => 'underline',
            'duration' => 900,
            'delay' => 250,
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('hero-fx-underline', false)
            ->assertSee('--hero-title-duration:900ms', false)
            ->assertSee('--hero-title-delay:250ms', false)
            ->assertSee('--hero-title-repeat:infinite', false);
    }

    /**
     * Inti dari memindahkan pengaturan ini ke tiap slide: satu hero boleh
     * mencampur beberapa gaya sekaligus.
     */
    public function test_each_slide_plays_its_own_effect(): void
    {
        $this->slide(['effect' => 'typing'], 'Slide Pertama');
        $this->slide(['effect' => 'highlight', 'accent_color' => '#08484a'], 'Slide Kedua');
        $this->slide([], 'Slide Ketiga');

        $response = $this->get(route('home'))->assertOk();

        $response->assertSee('hero-fx-typing', false)
            ->assertSee('hero-fx-highlight', false)
            ->assertSee('--hero-title-accent:#08484a', false)
            // Slide ketiga tetap polos: hanya kelas dasarnya, tanpa kelas efek
            ->assertSee('class="hero-title text-3xl', false);
    }

    public function test_turning_off_the_loop_runs_the_animation_once(): void
    {
        $this->slide(['effect' => 'wipe', 'loop' => false]);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('--hero-title-repeat:1', false)
            ->assertDontSee('--hero-title-repeat:infinite', false);
    }

    public function test_the_colours_are_passed_as_custom_properties(): void
    {
        $this->slide([
            'effect' => 'highlight',
            'color' => '#FFEEDD',
            'accent_color' => '#08484a',
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('--hero-title-color:#ffeedd', false)
            ->assertSee('--hero-title-accent:#08484a', false);
    }

    /**
     * Warna judul berakhir di dalam atribut `style`, jadi nilai yang bukan HEX
     * harus hilang sama sekali — bukan diselipkan apa adanya.
     */
    public function test_a_colour_that_is_not_hex_is_dropped(): void
    {
        $this->slide([
            'effect' => 'glow',
            'color' => 'red;background:url(javascript:alert(1))',
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertDontSee('--hero-title-color:', false)
            ->assertDontSee('javascript:alert', false);
    }

    public function test_typing_types_the_title_out_and_shows_a_caret(): void
    {
        $this->slide(['effect' => 'typing']);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('hero-fx-typing', false)
            ->assertSee('heroTitleTyping(', false)
            ->assertSee('x-text="typed"', false)
            ->assertSee('hero-title-caret', false)
            // Judulnya tetap ada di HTML mentah, jadi mesin telusur & pengunjung
            // tanpa JavaScript tidak kehilangan apa pun
            ->assertSee('Sekolah Unggulan');
    }

    public function test_the_typing_caret_can_be_hidden(): void
    {
        $this->slide(['effect' => 'typing', 'caret' => false]);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('heroTitleTyping(', false)
            ->assertDontSee('hero-title-caret"', false);
    }

    /**
     * Kursor mengetik tidak berarti apa-apa untuk efek lain, jadi tak ikut
     * tercetak walau saklarnya menyala.
     */
    public function test_the_caret_belongs_to_typing_only(): void
    {
        $this->slide(['effect' => 'shine', 'caret' => true]);

        $this->get(route('home'))
            ->assertOk()
            ->assertDontSee('hero-title-caret"', false);
    }

    public function test_an_unknown_effect_falls_back_to_none(): void
    {
        $this->slide(['effect' => 'salto']);

        $this->get(route('home'))
            ->assertOk()
            ->assertDontSee('hero-fx-', false);
    }

    public function test_timing_outside_the_panel_range_is_pulled_back_in(): void
    {
        $this->slide([
            'effect' => 'fade-up',
            'duration' => 99999,
            'delay' => -500,
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('--hero-title-duration:'.HeroTitleEffect::MAX_DURATION.'ms', false)
            ->assertSee('--hero-title-delay:'.HeroTitleEffect::MIN_DELAY.'ms', false);
    }

    /**
     * Hero cadangan tampil saat belum ada slide sama sekali, jadi tidak ada
     * slide yang bisa memiliki efek — judulnya harus tetap polos.
     */
    public function test_the_fallback_hero_stays_plain(): void
    {
        Setting::set('site_name', 'Pondok Nuris');

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Pondok Nuris')
            ->assertDontSee('hero-fx-', false)
            ->assertDontSee('hero-title-text', false);
    }

    public function test_the_typing_speed_follows_the_length_of_the_title(): void
    {
        $effect = new HeroTitleEffect(effect: 'typing', duration: 2000);

        // 2000 ms dibagi 10 huruf; judul dua kali lebih panjang diketik dua kali cepat
        $this->assertSame(200, $effect->typingSpeedFor('Sepuluhhur'));
        $this->assertSame(100, $effect->typingSpeedFor('Sepuluhhurufduapuluh'));
    }
}
