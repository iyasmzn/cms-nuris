<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\Programs\Pages\EditProgram;
use App\Filament\Resources\StaticPages\Pages\CreateStaticPage;
use App\Filament\Resources\StaticPages\Pages\EditStaticPage;
use App\Models\Media;
use App\Models\Program;
use App\Models\Slide;
use App\Models\StaticPage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Form halaman: seksi punya latar & anchornya sendiri, cover hero punya pilihan
 * media yang sama dengan slide halaman depan, dan sidebar kanannya opsional.
 */
class StaticPageSectionFormTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_page_form_starts_with_one_text_section(): void
    {
        $blocks = Livewire::actingAs($this->panelUser('StaticPage'))
            ->test(CreateStaticPage::class)
            ->get('data.blocks');

        // Kunci item repeater berupa uuid, jadi yang diperiksa isinya.
        $this->assertCount(1, $blocks);
        $this->assertSame('rich_text', array_values($blocks)[0]['type']);
    }

    public function test_section_background_and_sidebar_settings_are_saved(): void
    {
        $media = Media::factory()->create(['path' => 'media/masjid.jpg']);
        $page = StaticPage::factory()->create(['blocks' => [], 'show_sidebar' => false]);

        Livewire::actingAs($this->panelUser('StaticPage'))
            ->test(EditStaticPage::class, ['record' => $page->id])
            ->fillForm([
                'show_sidebar' => true,
                'blocks' => [
                    [
                        'type' => 'rich_text',
                        'eyebrow' => 'Tentang Kami',
                        'heading' => 'Profil Pesantren',
                        'content' => '<p>Berdiri sejak 1980.</p>',
                        'anchor' => 'profil',
                        'padding' => 'lg',
                        'background' => 'image',
                        'background_image_source' => 'library',
                        'background_image_library' => $media->id,
                        'background_overlay' => 45,
                        'background_light_text' => true,
                    ],
                ],
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $page->refresh();
        $block = $page->blocks[0];

        $this->assertTrue($page->show_sidebar);
        $this->assertSame('Profil Pesantren', $block['heading']);
        $this->assertSame('profil', $block['anchor']);
        $this->assertSame('lg', $block['padding']);
        $this->assertSame('media/masjid.jpg', $block['background_image']);
        $this->assertArrayNotHasKey('background_image_source', $block);
        $this->assertSame(45, (int) $block['background_overlay']);
    }

    public function test_hero_cover_youtube_is_saved(): void
    {
        $page = StaticPage::factory()->create(['hero' => null]);

        Livewire::actingAs($this->panelUser('StaticPage'))
            ->test(EditStaticPage::class, ['record' => $page->id])
            ->fillForm([
                'hero' => [
                    'media_type' => Slide::MEDIA_YOUTUBE,
                    'video_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
                    'video_preview_enabled' => true,
                    'show_video_button' => true,
                    'video_button_label' => 'Tonton Profil',
                ],
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $hero = $page->refresh()->hero_cover;

        $this->assertSame(Slide::MEDIA_YOUTUBE, $hero->mediaType);
        $this->assertTrue($hero->hasVideoBackground());
        $this->assertTrue($hero->showsVideoButton());
        $this->assertSame('Tonton Profil', $hero->videoButtonText());
    }

    public function test_program_form_saves_sections_and_hero(): void
    {
        // Kategori seed-an pabrik tidak selalu ada di daftar pilihan form.
        $program = Program::factory()->create(['blocks' => [], 'hero' => null, 'category' => null]);

        Livewire::actingAs($this->panelUser('Program'))
            ->test(EditProgram::class, ['record' => $program->id])
            ->fillForm([
                'show_sidebar' => true,
                'blocks' => [
                    ['type' => 'rich_text', 'heading' => 'Kurikulum', 'content' => '<p>Tahap hafalan.</p>'],
                ],
                'hero' => ['media_type' => Slide::MEDIA_IMAGE],
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $program->refresh();

        $this->assertTrue($program->show_sidebar);
        $this->assertSame('Kurikulum', $program->blocks[0]['heading']);
        $this->assertSame(Slide::MEDIA_IMAGE, $program->hero_cover->mediaType);
    }

    public function test_hero_youtube_url_must_be_a_youtube_video(): void
    {
        $page = StaticPage::factory()->create();

        Livewire::actingAs($this->panelUser('StaticPage'))
            ->test(EditStaticPage::class, ['record' => $page->id])
            ->fillForm([
                'hero' => [
                    'media_type' => Slide::MEDIA_YOUTUBE,
                    'video_url' => 'https://vimeo.com/12345',
                ],
            ])
            ->call('save')
            ->assertHasFormErrors(['hero.video_url']);
    }
}
