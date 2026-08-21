<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\Slides\Pages\CreateSlide;
use App\Filament\Resources\Slides\Pages\EditSlide;
use App\Models\Media;
use App\Models\Slide;
use App\Models\User;
use App\Services\EmbedVideo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SlideResourceTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return $this->panelUser('Slide');
    }

    public function test_youtube_slide_is_added_to_the_media_library(): void
    {
        $this->actingAs($this->admin());

        Livewire::test(CreateSlide::class)
            ->fillForm([
                'media_type' => Slide::MEDIA_YOUTUBE,
                'video_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
                'title' => 'Profil Pesantren',
                'sort_order' => 0,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('slides', [
            'title' => 'Profil Pesantren',
            'media_type' => Slide::MEDIA_YOUTUBE,
        ]);

        $this->assertDatabaseHas('media', [
            'embed_provider' => EmbedVideo::PROVIDER_YOUTUBE,
            'embed_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
            'name' => 'Profil Pesantren',
        ]);
    }

    public function test_preview_url_is_added_to_the_media_library(): void
    {
        $this->actingAs($this->admin());

        Livewire::test(CreateSlide::class)
            ->fillForm([
                'media_type' => Slide::MEDIA_IMAGE,
                'title' => 'Slide Gambar',
                'sort_order' => 0,
                'video_preview_enabled' => true,
                'preview_video_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
                'show_video_button' => true,
                'video_button_label' => 'Tonton Profil',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('media', [
            'embed_provider' => EmbedVideo::PROVIDER_YOUTUBE,
            'embed_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
        ]);

        $slide = Slide::firstOrFail();

        $this->assertTrue($slide->showsVideoButton());
        $this->assertSame('Tonton Profil', $slide->video_button_text);
    }

    public function test_the_same_video_is_never_added_to_the_library_twice(): void
    {
        $this->actingAs($this->admin());

        $url = 'https://www.youtube.com/watch?v=dQw4w9WgXcQ';

        foreach (['Slide Satu', 'Slide Dua'] as $title) {
            Livewire::test(CreateSlide::class)
                ->fillForm([
                    'media_type' => Slide::MEDIA_YOUTUBE,
                    'video_url' => $url,
                    'title' => $title,
                    'sort_order' => 0,
                ])
                ->call('create')
                ->assertHasNoFormErrors();
        }

        $this->assertSame(1, Media::query()->where('embed_url', $url)->count());
    }

    public function test_video_can_be_picked_from_the_media_library(): void
    {
        $this->actingAs($this->admin());

        $video = Media::factory()->create([
            'path' => 'media/profil.mp4',
            'mime_type' => 'video/mp4',
            'name' => 'Video Profil',
        ]);

        Livewire::test(CreateSlide::class)
            ->fillForm([
                'media_type' => Slide::MEDIA_VIDEO,
                'video_path_source' => 'library',
                'video_path_library' => $video->id,
                'title' => 'Slide Video',
                'sort_order' => 0,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('slides', [
            'title' => 'Slide Video',
            'media_type' => Slide::MEDIA_VIDEO,
            'video_path' => 'media/profil.mp4',
        ]);

        $this->assertSame(1, Media::query()->count());
    }

    public function test_youtube_background_requires_a_valid_youtube_url(): void
    {
        $this->actingAs($this->admin());

        Livewire::test(CreateSlide::class)
            ->fillForm([
                'media_type' => Slide::MEDIA_YOUTUBE,
                'video_url' => 'https://vimeo.com/123456789',
                'title' => 'Slide Salah',
                'sort_order' => 0,
            ])
            ->call('create')
            ->assertHasFormErrors(['video_url']);
    }

    public function test_youtube_background_requires_a_url(): void
    {
        $this->actingAs($this->admin());

        Livewire::test(CreateSlide::class)
            ->fillForm([
                'media_type' => Slide::MEDIA_YOUTUBE,
                'video_url' => null,
                'title' => 'Slide Tanpa Video',
                'sort_order' => 0,
            ])
            ->call('create')
            ->assertHasFormErrors(['video_url' => 'required']);
    }

    public function test_preview_url_must_be_a_supported_video_link(): void
    {
        $this->actingAs($this->admin());

        Livewire::test(CreateSlide::class)
            ->fillForm([
                'media_type' => Slide::MEDIA_IMAGE,
                'title' => 'Slide Gambar',
                'sort_order' => 0,
                'video_preview_enabled' => true,
                'preview_video_url' => 'https://example.com/video.mp4',
            ])
            ->call('create')
            ->assertHasFormErrors(['preview_video_url']);
    }

    public function test_editing_a_slide_syncs_its_video_to_the_media_library(): void
    {
        $this->actingAs($this->admin());

        $slide = Slide::factory()->create(['title' => 'Slide Lama']);

        Livewire::test(EditSlide::class, ['record' => $slide->id])
            ->fillForm([
                'media_type' => Slide::MEDIA_YOUTUBE,
                'video_url' => 'https://youtu.be/dQw4w9WgXcQ',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('media', [
            'embed_provider' => EmbedVideo::PROVIDER_YOUTUBE,
            'embed_url' => 'https://youtu.be/dQw4w9WgXcQ',
        ]);
    }

    /**
     * Slide yang dibuat sebelum efek judul ada bernilai null. Kolom animasinya
     * wajib diisi, jadi tanpa dinormalkan saat form dimuat slide-slide lama
     * akan tertahan validasi dan tak bisa disimpan sama sekali.
     */
    public function test_a_slide_without_a_stored_title_effect_can_still_be_saved(): void
    {
        $this->actingAs($this->admin());

        $slide = Slide::factory()->create(['title' => 'Slide Lama', 'title_effect' => null]);

        Livewire::test(EditSlide::class, ['record' => $slide->id])
            ->fillForm(['title' => 'Slide Diperbarui'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('slides', ['id' => $slide->id, 'title' => 'Slide Diperbarui']);
        $this->assertSame('none', $slide->refresh()->titleEffect()->effect);
    }

    public function test_the_title_effect_is_saved_with_the_slide(): void
    {
        $this->actingAs($this->admin());

        Livewire::test(CreateSlide::class)
            ->fillForm([
                'media_type' => Slide::MEDIA_IMAGE,
                'title' => 'Selamat Datang',
                'sort_order' => 0,
                'title_effect' => [
                    'color' => '#ffffff',
                    'effect' => 'typing',
                    'accent_color' => '#08484a',
                    'duration' => 2400,
                    'delay' => 300,
                    'loop' => false,
                    'caret' => false,
                ],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $effect = Slide::query()->where('title', 'Selamat Datang')->sole()->titleEffect();

        $this->assertSame('typing', $effect->effect);
        $this->assertSame('#ffffff', $effect->color);
        $this->assertSame('#08484a', $effect->accentColor);
        $this->assertSame(2400, $effect->duration);
        $this->assertSame(300, $effect->delay);
        $this->assertFalse($effect->loop);
        $this->assertFalse($effect->caret);
    }

    /**
     * Warna judul berakhir di dalam atribut `style` halaman depan, jadi panel
     * menolaknya di depan alih-alih membiarkannya lolos lalu hilang diam-diam.
     */
    public function test_a_title_colour_that_is_not_hex_is_rejected(): void
    {
        $this->actingAs($this->admin());

        Livewire::test(CreateSlide::class)
            ->fillForm([
                'media_type' => Slide::MEDIA_IMAGE,
                'title' => 'Selamat Datang',
                'sort_order' => 0,
                'title_effect' => [
                    'effect' => 'underline',
                    'color' => 'merah menyala',
                ],
            ])
            ->call('create')
            ->assertHasFormErrors();

        $this->assertDatabaseMissing('slides', ['title' => 'Selamat Datang']);
    }

    /**
     * Pratinjau judul menumpang stylesheet halaman depan dan merangkai HTML-nya
     * sendiri, jadi ia perlu dibuktikan benar-benar tercetak — bukan sekadar
     * tidak meledak saat kolomnya tersembunyi.
     */
    public function test_the_title_effect_preview_is_rendered_once_an_effect_is_chosen(): void
    {
        $this->actingAs($this->admin());

        $slide = Slide::factory()->create([
            'title' => 'Selamat Datang',
            'title_effect' => ['effect' => 'typing', 'accent_color' => '#08484a'],
        ]);

        Livewire::test(EditSlide::class, ['record' => $slide->id])
            ->assertSee('hero-title-preview', false)
            ->assertSee('hero-fx-typing', false)
            ->assertSee('--hero-title-accent:#08484a', false);
    }
}
