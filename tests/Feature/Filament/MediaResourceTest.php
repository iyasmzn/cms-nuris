<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\Media\Pages\ListMedia;
use App\Models\Media;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class MediaResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_video_without_a_poster_previews_its_first_frame(): void
    {
        $this->actingAs($this->panelUser('Media'));

        Media::factory()->videoFile('media/profil.mp4')->create(['name' => 'Video Profil']);

        Livewire::test(ListMedia::class)
            ->assertSee('Video Profil')
            ->assertSee('<video', false)
            ->assertSee(asset('storage/media/profil.mp4').'#t=0.5', false);
    }

    public function test_video_with_a_poster_shows_the_uploaded_image(): void
    {
        $this->actingAs($this->panelUser('Media'));

        Media::factory()
            ->videoFile()
            ->withEmbedThumbnail('media/embed-thumbnails/poster.jpg')
            ->create(['name' => 'Video Berposter']);

        Livewire::test(ListMedia::class)
            ->assertSee(asset('storage/media/embed-thumbnails/poster.jpg'), false)
            ->assertDontSee('<video', false);
    }

    public function test_image_media_still_renders_as_an_image(): void
    {
        $this->actingAs($this->panelUser('Media'));

        Media::factory()->create(['name' => 'Foto Wisuda', 'path' => 'media/wisuda.jpg']);

        Livewire::test(ListMedia::class)
            ->assertSee(asset('storage/media/wisuda.jpg'), false)
            ->assertDontSee('<video', false);
    }
}
