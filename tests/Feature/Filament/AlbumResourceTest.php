<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\Albums\Pages\CreateAlbum;
use App\Filament\Resources\Albums\Pages\EditAlbum;
use App\Filament\Resources\Albums\Pages\ListAlbums;
use App\Filament\Resources\Albums\RelationManagers\MediaRelationManager;
use App\Models\Album;
use App\Models\Media;
use App\Models\User;
use Filament\Actions\DissociateAction;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AlbumResourceTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return $this->panelUser('Album');
    }

    public function test_album_list_shows_media_counts(): void
    {
        $this->actingAs($this->admin());

        $album = Album::factory()->named('Wisuda 2025')->create();
        Media::factory()->count(3)->inAlbum($album)->create();
        Media::factory()->inGallery()->inAlbum($album)->create();

        Livewire::test(ListAlbums::class)
            ->assertCanSeeTableRecords([$album])
            ->assertSee('Wisuda 2025');

        $this->assertSame(4, $album->media()->count());
        $this->assertSame(1, $album->media()->where('show_in_gallery', true)->count());
    }

    public function test_admin_can_create_an_album(): void
    {
        $this->actingAs($this->admin());

        Livewire::test(CreateAlbum::class)
            ->fillForm(['name' => 'Fasilitas'])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('albums', ['name' => 'Fasilitas']);
    }

    public function test_duplicate_album_names_are_rejected(): void
    {
        $this->actingAs($this->admin());

        Album::factory()->named('Fasilitas')->create();

        Livewire::test(CreateAlbum::class)
            ->fillForm(['name' => 'Fasilitas'])
            ->call('create')
            ->assertHasFormErrors(['name' => 'unique']);

        $this->assertSame(1, Album::query()->count());
    }

    public function test_renaming_an_album_applies_to_all_its_media(): void
    {
        $this->actingAs($this->admin());

        $album = Album::factory()->named('Wisuda 2024')->create();
        $media = Media::factory()->count(2)->inAlbum($album)->create();

        Livewire::test(EditAlbum::class, ['record' => $album->id])
            ->fillForm(['name' => 'Wisuda 2025'])
            ->call('save')
            ->assertHasNoFormErrors();

        foreach ($media as $item) {
            $this->assertSame('Wisuda 2025', $item->refresh()->album->name);
        }
    }

    public function test_deleting_an_album_keeps_its_media(): void
    {
        $this->actingAs($this->admin());

        $album = Album::factory()->create();
        $media = Media::factory()->inAlbum($album)->create();

        $album->delete();

        $media->refresh();

        $this->assertNull($media->album_id);
        $this->assertDatabaseHas('media', ['id' => $media->id]);
    }

    public function test_album_list_previews_its_media(): void
    {
        $this->actingAs($this->admin());

        $album = Album::factory()->named('Wisuda 2025')->create();
        Media::factory()->inAlbum($album)->create(['path' => 'media/prosesi.jpg']);
        Media::factory()->inAlbum($album)->videoFile('media/sambutan.mp4')->create();

        Livewire::test(ListAlbums::class)
            ->assertSee(asset('storage/media/prosesi.jpg'), false)
            ->assertSee(asset('storage/media/sambutan.mp4').'#t=0.5', false);
    }

    public function test_empty_album_says_so_in_the_list(): void
    {
        $this->actingAs($this->admin());

        Album::factory()->named('Album Baru')->create();

        Livewire::test(ListAlbums::class)
            ->assertSee('Album kosong');
    }

    public function test_album_contents_are_listed_on_its_edit_page(): void
    {
        $this->actingAs($this->panelUser('Album', 'Media'));

        $album = Album::factory()->create();
        $inside = Media::factory()->inAlbum($album)->create(['name' => 'Foto Prosesi']);
        $outside = Media::factory()->create(['name' => 'Foto Lain']);

        Livewire::test(MediaRelationManager::class, [
            'ownerRecord' => $album,
            'pageClass' => EditAlbum::class,
        ])
            ->assertCanSeeTableRecords([$inside])
            ->assertCanNotSeeTableRecords([$outside]);
    }

    public function test_media_can_be_removed_from_an_album_without_deleting_it(): void
    {
        $this->actingAs($this->panelUser('Album', 'Media'));

        $album = Album::factory()->create();
        $media = Media::factory()->inAlbum($album)->create();

        Livewire::test(MediaRelationManager::class, [
            'ownerRecord' => $album,
            'pageClass' => EditAlbum::class,
        ])
            ->callAction(TestAction::make(DissociateAction::class)->table($media));

        $this->assertNull($media->refresh()->album_id);
        $this->assertDatabaseHas('media', ['id' => $media->id]);
    }
}
