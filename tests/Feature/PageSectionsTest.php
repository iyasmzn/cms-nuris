<?php

namespace Tests\Feature;

use App\Models\Program;
use App\Models\Slide;
use App\Models\StaticPage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Halaman & program kini disusun dari seksi: lebar penuh secara bawaan, kartu
 * bersudut membulat bila sidebar kanannya dinyalakan.
 */
class PageSectionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_page_without_sidebar_renders_full_width_sections(): void
    {
        $page = StaticPage::factory()->create([
            'show_sidebar' => false,
            'blocks' => [
                ['type' => 'rich_text', 'content' => '<p>Isi seksi pertama.</p>', 'heading' => 'Profil Singkat'],
            ],
        ]);

        $this->get(route('page.show', $page->slug))
            ->assertOk()
            ->assertSee('block-section block-section-full', false)
            ->assertSee('Profil Singkat')
            ->assertSee('Isi seksi pertama.', false)
            ->assertDontSee('block-section block-section-boxed', false)
            ->assertDontSee('class="sidebar-sticky', false);
    }

    public function test_page_with_sidebar_renders_boxed_sections_and_table_of_contents(): void
    {
        $page = StaticPage::factory()->create([
            'show_sidebar' => true,
            'blocks' => [
                ['type' => 'rich_text', 'content' => '<p>Satu.</p>', 'heading' => 'Bagian Satu'],
                ['type' => 'rich_text', 'content' => '<p>Dua.</p>', 'heading' => 'Bagian Dua'],
            ],
        ]);

        $this->get(route('page.show', $page->slug))
            ->assertOk()
            ->assertSee('block-section block-section-boxed', false)
            ->assertDontSee('block-section block-section-full', false)
            ->assertSee('class="sidebar-sticky', false)
            ->assertSee('Daftar Isi')
            ->assertSee('Bagian Satu')
            ->assertSee('Bagian Dua');
    }

    public function test_section_background_image_and_anchor_are_applied(): void
    {
        $page = StaticPage::factory()->create([
            'blocks' => [
                [
                    'type' => 'rich_text',
                    'content' => '<p>Di atas gambar.</p>',
                    'anchor' => 'Fasilitas Kami',
                    'padding' => 'lg',
                    'background' => 'image',
                    'background_image' => 'pages/blocks/backgrounds/masjid.jpg',
                    'background_overlay' => 45,
                    'background_light_text' => true,
                ],
            ],
        ]);

        $this->get(route('page.show', $page->slug))
            ->assertOk()
            ->assertSee('id="fasilitas-kami"', false)
            ->assertSee('block-pad-lg', false)
            ->assertSee('section-light', false)
            ->assertSee('pages/blocks/backgrounds/masjid.jpg', false)
            ->assertSee('rgba(17,24,39,0.45)', false);
    }

    public function test_meta_description_falls_back_to_block_text(): void
    {
        $page = StaticPage::factory()->create([
            'meta_description' => null,
            'blocks' => [
                ['type' => 'rich_text', 'content' => '<p>Pesantren modern berbasis tahfidz.</p>'],
            ],
        ]);

        $this->get(route('page.show', $page->slug))
            ->assertOk()
            ->assertSee('name="description" content="Pesantren modern berbasis tahfidz."', false);
    }

    public function test_page_hero_renders_youtube_cover_with_preview_button(): void
    {
        $page = StaticPage::factory()->create([
            'hero' => [
                'media_type' => Slide::MEDIA_YOUTUBE,
                'video_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
                'video_preview_enabled' => true,
                'show_video_button' => true,
                'video_button_label' => 'Profil Video',
            ],
        ]);

        $this->get(route('page.show', $page->slug))
            ->assertOk()
            ->assertSee('youtube-nocookie.com/embed/dQw4w9WgXcQ', false)
            ->assertSee('Profil Video')
            ->assertSee('page-hero-media', false);
    }

    public function test_page_without_hero_media_keeps_gradient_hero(): void
    {
        $page = StaticPage::factory()->create(['hero' => null]);

        $this->get(route('page.show', $page->slug))
            ->assertOk()
            ->assertSee('page-hero-circle', false)
            ->assertDontSee('page-hero-media', false);
    }

    public function test_section_heading_alignment_applies_to_any_block_type(): void
    {
        $page = StaticPage::factory()->create([
            'blocks' => [
                [
                    'type' => 'cards',
                    'eyebrow' => 'Unggulan',
                    'heading' => 'Fasilitas Kami',
                    'heading_align' => 'center',
                    'items' => [['title' => 'Masjid']],
                ],
                [
                    'type' => 'rich_text',
                    'heading' => 'Sejarah',
                    'content' => '<p>Berdiri 1973.</p>',
                ],
            ],
        ]);

        $this->get(route('page.show', $page->slug))
            ->assertOk()
            ->assertSee('block-section-head block-text-center', false)
            ->assertSee('block-section-head block-text-left', false);
    }

    public function test_media_text_block_applies_the_chosen_text_alignment(): void
    {
        $page = StaticPage::factory()->create([
            'blocks' => [
                [
                    'type' => 'media_text',
                    'heading' => 'Fasilitas',
                    'text' => '<p>Asrama luas.</p>',
                    'media_image' => 'pages/blocks/asrama.jpg',
                    'text_align' => 'center',
                ],
            ],
        ]);

        $this->get(route('page.show', $page->slug))
            ->assertOk()
            ->assertSee('class="block-text-center', false);
    }

    public function test_media_text_block_defaults_to_left_alignment(): void
    {
        $page = StaticPage::factory()->create([
            'blocks' => [
                ['type' => 'media_text', 'text' => '<p>Tanpa pengaturan perataan.</p>'],
            ],
        ]);

        $this->get(route('page.show', $page->slug))
            ->assertOk()
            ->assertSee('class="block-text-left', false);
    }

    public function test_page_emits_webpage_and_breadcrumb_structured_data(): void
    {
        $page = StaticPage::factory()->create([
            'title' => 'Sejarah',
            'slug' => 'sejarah',
            'meta_description' => 'Sejarah berdirinya pesantren.',
            'hero' => ['media_type' => Slide::MEDIA_IMAGE, 'image' => 'pages/hero/gedung.jpg'],
        ]);

        $response = $this->get(route('page.show', $page->slug))->assertOk();

        $response->assertSee('application/ld+json', false)
            ->assertSee('"@type": "WebPage"', false)
            ->assertSee('"description": "Sejarah berdirinya pesantren."', false)
            ->assertSee('pages/hero/gedung.jpg', false)
            ->assertSee('"@type": "BreadcrumbList"', false)
            ->assertSee('"position": 2', false);
    }

    public function test_program_emits_structured_data_with_program_breadcrumb(): void
    {
        $program = Program::factory()->create([
            'title' => 'Tahfidz',
            'is_published' => true,
            'excerpt' => 'Program menghafal Al-Qur\'an.',
        ]);

        $this->get(route('programs.show', $program))
            ->assertOk()
            ->assertSee('"@type": "WebPage"', false)
            ->assertSee('"@type": "BreadcrumbList"', false)
            ->assertSee('"name": "Program"', false)
            ->assertSee('"position": 3', false);
    }

    public function test_program_sections_and_hero_video_cover(): void
    {
        $program = Program::factory()->create([
            'is_published' => true,
            'show_sidebar' => false,
            'blocks' => [
                ['type' => 'rich_text', 'content' => '<p>Detail program tahfidz.</p>', 'heading' => 'Kurikulum'],
            ],
            'hero' => [
                'media_type' => Slide::MEDIA_VIDEO,
                'video_path' => 'programs/hero/tahfidz.mp4',
            ],
        ]);

        $this->get(route('programs.show', $program))
            ->assertOk()
            ->assertSee('block-section block-section-full', false)
            ->assertSee('Kurikulum')
            ->assertSee('Detail program tahfidz.', false)
            ->assertSee('programs/hero/tahfidz.mp4', false);
    }
}
