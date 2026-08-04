<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\Media\Pages\EditMedia;
use App\Filament\Resources\Media\Pages\ListMedia;
use App\Models\Album;
use App\Models\Media;
use Filament\Actions\Testing\TestAction;
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

    public function test_media_can_be_grouped_into_a_new_album_on_upload(): void
    {
        $this->actingAs($this->panelUser('Media'));

        $album = Album::factory()->named('Wisuda 2025')->create();

        $media = Media::factory()->inAlbum($album)->create(['name' => 'Foto Prosesi']);

        $this->assertSame('Wisuda 2025', $media->album->name);

        Livewire::test(ListMedia::class)
            ->assertSee('Wisuda 2025');
    }

    public function test_album_filter_limits_the_library_to_one_album(): void
    {
        $this->actingAs($this->panelUser('Media'));

        $wisuda = Album::factory()->named('Wisuda')->create();
        $inAlbum = Media::factory()->inAlbum($wisuda)->create(['name' => 'Foto Wisuda']);
        $other = Media::factory()->create(['name' => 'Foto Lain']);

        Livewire::test(ListMedia::class)
            ->filterTable('album_id', $wisuda->id)
            ->assertCanSeeTableRecords([$inAlbum])
            ->assertCanNotSeeTableRecords([$other]);
    }

    public function test_album_can_be_created_from_the_media_edit_form(): void
    {
        $this->actingAs($this->panelUser('Media'));

        $media = Media::factory()->create(['name' => 'Foto Kegiatan']);

        Livewire::test(EditMedia::class, ['record' => $media->id])
            ->callAction(
                TestAction::make('createOption')->schemaComponent('album_id'),
                ['name' => 'Video Kumpulan'],
            )
            ->assertHasNoActionErrors();

        $this->assertDatabaseHas('albums', ['name' => 'Video Kumpulan']);
    }

    public function test_creating_an_album_that_already_exists_is_rejected(): void
    {
        $this->actingAs($this->panelUser('Media'));

        Album::factory()->named('Wisuda 2025')->create();
        $media = Media::factory()->create();

        Livewire::test(EditMedia::class, ['record' => $media->id])
            ->callAction(
                TestAction::make('createOption')->schemaComponent('album_id'),
                ['name' => 'Wisuda 2025'],
            )
            ->assertHasActionErrors(['name' => 'unique']);

        $this->assertSame(1, Album::query()->count());
    }

    public function test_album_can_be_renamed_from_the_media_edit_form(): void
    {
        $this->actingAs($this->panelUser('Media'));

        $album = Album::factory()->named('Wisuda 2024')->create();
        $media = Media::factory()->inAlbum($album)->create();

        Livewire::test(EditMedia::class, ['record' => $media->id])
            ->callAction(
                TestAction::make('editOption')->schemaComponent('album_id'),
                ['name' => 'Wisuda 2025'],
            )
            ->assertHasNoActionErrors();

        $this->assertSame('Wisuda 2025', $album->refresh()->name);
    }
}
