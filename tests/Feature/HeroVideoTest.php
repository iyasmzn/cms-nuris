<?php

namespace Tests\Feature;

use App\Models\Slide;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HeroVideoTest extends TestCase
{
    use RefreshDatabase;

    public function test_image_slide_renders_no_video(): void
    {
        Slide::factory()->create(['title' => 'Slide Gambar']);

        $response = $this->get(route('home'));

        $response->assertOk()
            ->assertSee('Slide Gambar')
            ->assertDontSee('<source', false)
            ->assertDontSee('youtube-nocookie', false);
    }

    public function test_uploaded_video_slide_renders_muted_looping_video(): void
    {
        Slide::factory()->videoFile('slides/profil.mp4')->create();

        $response = $this->get(route('home'));

        $response->assertOk()
            ->assertSee('<video', false)
            ->assertSee('muted loop playsinline autoplay', false)
            ->assertSee(asset('storage/slides/profil.mp4'), false);
    }

    public function test_youtube_slide_renders_muted_looping_background_iframe(): void
    {
        Slide::factory()->youtube('https://youtu.be/dQw4w9WgXcQ')->create();

        $response = $this->get(route('home'));

        $response->assertOk()
            ->assertSee('youtube-nocookie', false)
            ->assertSee('dQw4w9WgXcQ', false)
            ->assertSee('autoplay=1', false)
            ->assertSee('mute=1', false)
            ->assertSee('loop=1', false);
    }

    public function test_unparseable_youtube_url_falls_back_to_the_image(): void
    {
        Slide::factory()->youtube('https://www.youtube.com/@channel')->create();

        $response = $this->get(route('home'));

        $response->assertOk()
            ->assertDontSee('youtube-nocookie', false);
    }

    public function test_preview_button_uses_custom_label(): void
    {
        Slide::factory()
            ->youtube()
            ->withVideoPreview(label: 'Tonton Profil Pesantren')
            ->create();

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Tonton Profil Pesantren')
            ->assertSee('openVideo(JSON.parse', false);
    }

    public function test_preview_button_falls_back_to_the_default_label(): void
    {
        Slide::factory()->youtube()->withVideoPreview()->create();

        $this->get(route('home'))
            ->assertOk()
            ->assertSee(Slide::DEFAULT_VIDEO_BUTTON_LABEL);
    }

    public function test_preview_without_button_makes_the_video_area_clickable(): void
    {
        Slide::factory()
            ->videoFile()
            ->withVideoPreview(withButton: false, label: 'Tonton Video Profil')
            ->create(['title' => 'Slide Video']);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Putar video: Slide Video')
            ->assertDontSee('Tonton Video Profil');
    }

    public function test_disabled_preview_renders_no_player(): void
    {
        Slide::factory()->youtube()->create();

        $this->get(route('home'))
            ->assertOk()
            ->assertDontSee('openVideo(JSON.parse', false);
    }

    public function test_preview_url_overrides_the_background_video(): void
    {
        Slide::factory()
            ->videoFile('slides/latar.mp4')
            ->withVideoPreview(url: 'https://www.youtube.com/watch?v=dQw4w9WgXcQ')
            ->create();

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('dQw4w9WgXcQ', false)
            ->assertDontSee('youtube-nocookie', false);
    }

    public function test_image_slide_can_still_have_a_video_preview(): void
    {
        Slide::factory()
            ->withVideoPreview(url: 'https://www.youtube.com/watch?v=dQw4w9WgXcQ')
            ->create();

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('openVideo(JSON.parse', false)
            ->assertSee(Slide::DEFAULT_VIDEO_BUTTON_LABEL);
    }

    public function test_preview_enabled_without_any_video_renders_no_player(): void
    {
        Slide::factory()->withVideoPreview()->create();

        $this->get(route('home'))
            ->assertOk()
            ->assertDontSee('openVideo(JSON.parse', false);
    }
}
