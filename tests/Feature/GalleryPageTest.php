<?php

namespace Tests\Feature;

use App\Models\Media;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GalleryPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_page_lists_only_flagged_media(): void
    {
        $shown = Media::factory()->inGallery()->create(['name' => 'Foto Wisuda']);
        $hidden = Media::factory()->create(['name' => 'Foto Internal']);

        $this->get(route('gallery.index'))
            ->assertOk()
            ->assertSee('Foto Wisuda')
            ->assertDontSee('Foto Internal');
    }

    public function test_index_page_excludes_non_visual_media(): void
    {
        $pdf = Media::factory()->inGallery()->create([
            'name' => 'Brosur PDF',
            'mime_type' => 'application/pdf',
        ]);

        $this->get(route('gallery.index'))
            ->assertOk()
            ->assertDontSee('Brosur PDF');
    }

    public function test_album_filter_limits_to_matching_media(): void
    {
        $wisuda = Media::factory()->inGallery()->inAlbum('Wisuda 2025')->create(['name' => 'Prosesi Wisuda']);
        $fasilitas = Media::factory()->inGallery()->inAlbum('Fasilitas')->create(['name' => 'Gedung Asrama']);

        $this->get(route('gallery.index', ['album' => 'Wisuda 2025']))
            ->assertOk()
            ->assertSee('Prosesi Wisuda')
            ->assertDontSee('Gedung Asrama');
    }

    public function test_foto_filter_excludes_video_embeds(): void
    {
        $photo = Media::factory()->inGallery()->create(['name' => 'Foto Upacara']);
        $video = Media::factory()->inGallery()->embed()->create(['name' => 'Video Profil']);

        $this->get(route('gallery.index', ['type' => 'foto']))
            ->assertOk()
            ->assertSee('Foto Upacara')
            ->assertDontSee('Video Profil');
    }

    public function test_video_filter_excludes_photos(): void
    {
        $photo = Media::factory()->inGallery()->create(['name' => 'Foto Upacara']);
        $video = Media::factory()->inGallery()->embed()->create(['name' => 'Video Profil']);

        $this->get(route('gallery.index', ['type' => 'video']))
            ->assertOk()
            ->assertSee('Video Profil')
            ->assertDontSee('Foto Upacara');
    }

    public function test_home_page_gallery_section_shows_flagged_media(): void
    {
        $shown = Media::factory()->inGallery()->create(['name' => 'Kegiatan Sekolah']);
        $hidden = Media::factory()->create(['name' => 'Arsip Privat']);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Kegiatan Sekolah')
            ->assertDontSee('Arsip Privat');
    }

    public function test_uploaded_video_appears_in_the_gallery(): void
    {
        Media::factory()->inGallery()->videoFile('media/wisuda.mp4')->create(['name' => 'Video Wisuda']);

        $this->get(route('gallery.index'))
            ->assertOk()
            ->assertSee('Video Wisuda')
            ->assertSee(asset('storage/media/wisuda.mp4'), false)
            ->assertSee('\u0022type\u0022:\u0022file\u0022', false);
    }

    public function test_hidden_uploaded_video_stays_out_of_the_gallery(): void
    {
        Media::factory()->videoFile()->create(['name' => 'Video Internal']);

        $this->get(route('gallery.index'))
            ->assertOk()
            ->assertDontSee('Video Internal');
    }

    public function test_video_filter_includes_uploaded_videos(): void
    {
        Media::factory()->inGallery()->videoFile()->create(['name' => 'Video Kegiatan']);
        Media::factory()->inGallery()->create(['name' => 'Foto Upacara']);

        $this->get(route('gallery.index', ['type' => 'video']))
            ->assertOk()
            ->assertSee('Video Kegiatan')
            ->assertDontSee('Foto Upacara');
    }

    public function test_foto_filter_excludes_uploaded_videos(): void
    {
        Media::factory()->inGallery()->videoFile()->create(['name' => 'Video Kegiatan']);
        Media::factory()->inGallery()->create(['name' => 'Foto Upacara']);

        $this->get(route('gallery.index', ['type' => 'foto']))
            ->assertOk()
            ->assertSee('Foto Upacara')
            ->assertDontSee('Video Kegiatan');
    }

    public function test_home_page_gallery_section_shows_uploaded_videos(): void
    {
        Media::factory()->inGallery()->videoFile('media/profil.mp4')->create(['name' => 'Video Profil Sekolah']);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Video Profil Sekolah')
            ->assertSee(asset('storage/media/profil.mp4'), false);
    }

    public function test_uploaded_video_uses_its_manual_thumbnail(): void
    {
        Media::factory()
            ->inGallery()
            ->videoFile()
            ->withEmbedThumbnail('media/embed-thumbnails/poster.jpg')
            ->create(['name' => 'Video Berposter']);

        $this->get(route('gallery.index'))
            ->assertOk()
            ->assertSee(asset('storage/media/embed-thumbnails/poster.jpg'), false);
    }
}
