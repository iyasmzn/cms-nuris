<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\Slide;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HeroSliderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Slide::factory()->count(2)->create();
    }

    public function test_it_uses_the_default_slider_behaviour(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('hero-anim-fade', false)
            ->assertSee('--hero-duration: 700ms', false)
            ->assertSee('}, 5000)', false)
            ->assertSee('@mouseenter="heroPaused', false);
    }

    public function test_the_slide_content_is_centred_by_default(): void
    {
        $this->assertContains('hero-align-center', $this->heroClasses());
    }

    public function test_the_slide_content_follows_the_configured_alignment(): void
    {
        foreach (['left', 'right'] as $alignment) {
            Setting::set('hero_content_align', $alignment);

            $classes = $this->heroClasses();

            $this->assertContains("hero-align-{$alignment}", $classes);
            $this->assertNotContains('hero-align-center', $classes);
        }
    }

    public function test_an_unknown_alignment_falls_back_to_the_centre(): void
    {
        Setting::set('hero_content_align', 'diagonal');

        $classes = $this->heroClasses();

        $this->assertContains('hero-align-center', $classes);
        $this->assertNotContains('hero-align-diagonal', $classes);
    }

    /**
     * Kelas pada elemen hero itu sendiri. Blok `<style>` hero memuat semua
     * nama kelas perataan, jadi mencari teksnya di seluruh halaman tidak
     * membuktikan apa-apa.
     *
     * @return list<string>
     */
    private function heroClasses(): array
    {
        $html = $this->get(route('home'))->assertOk()->getContent();

        $this->assertSame(1, preg_match('/<section id="beranda"\s+class="([^"]*)"/', $html, $match));

        return preg_split('/\s+/', trim($match[1]));
    }

    public function test_the_configured_interval_transition_and_duration_are_rendered(): void
    {
        Setting::setMany([
            'hero_interval' => 9,
            'hero_transition' => 'slide',
            'hero_transition_duration' => 400,
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('hero-anim-slide', false)
            ->assertSee('--hero-duration: 400ms', false)
            ->assertSee('}, 9000)', false);
    }

    public function test_pause_on_hover_can_be_turned_off(): void
    {
        Setting::set('hero_pause_on_hover', false);

        $this->get(route('home'))
            ->assertOk()
            ->assertDontSee('@mouseenter="heroPaused', false)
            ->assertDontSee('heroPaused = true', false);
    }

    public function test_autoplay_can_be_turned_off_entirely(): void
    {
        Setting::set('hero_autoplay', false);

        $this->get(route('home'))
            ->assertOk()
            ->assertDontSee('setInterval', false);
    }

    public function test_an_unknown_transition_falls_back_to_fade(): void
    {
        Setting::setMany([
            'hero_transition' => 'kaleidoskop',
            'hero_interval' => 999,
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('hero-anim-fade', false)
            // Jeda di luar rentang panel dikembalikan ke batas maksimum
            ->assertSee('}, 20000)', false);
    }

    /**
     * The counter pill only makes sense while there is something to count
     * through; a lone slide would forever read "1 / 1".
     */
    public function test_the_slide_counter_is_hidden_when_there_is_only_one_slide(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('1 / 2');

        Slide::query()->delete();
        Slide::factory()->create();

        $this->get(route('home'))
            ->assertOk()
            ->assertDontSee('1 / 1');
    }
}
