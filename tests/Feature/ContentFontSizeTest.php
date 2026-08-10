<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\StaticPage;
use App\Support\ContentTypography;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Ukuran teks konten diatur sekali secara global lalu diteruskan ke stylesheet
 * lewat custom property `--content-scale`.
 */
class ContentFontSizeTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_defaults_to_the_original_design_size(): void
    {
        $this->assertSame(ContentTypography::DEFAULT_SIZE, ContentTypography::size());
        $this->assertSame(1.0, ContentTypography::scale());
    }

    public function test_it_scales_relative_to_the_default_size(): void
    {
        Setting::set(ContentTypography::SETTING_KEY, 16);

        $this->assertSame(16, ContentTypography::size());
        $this->assertSame(round(16 / 18, 4), ContentTypography::scale());
        $this->assertSame(round(16 / 18, 4), content_font_scale());
    }

    public function test_it_falls_back_to_the_default_for_an_unlisted_size(): void
    {
        Setting::set(ContentTypography::SETTING_KEY, 99);

        $this->assertSame(ContentTypography::DEFAULT_SIZE, ContentTypography::size());
        $this->assertSame(1.0, ContentTypography::scale());
    }

    public function test_public_layout_exposes_the_chosen_scale(): void
    {
        Setting::set(ContentTypography::SETTING_KEY, 15);

        $page = StaticPage::factory()->create([
            'blocks' => [
                ['type' => 'rich_text', 'content' => '<p>Isi halaman.</p>'],
            ],
        ]);

        $this->get(route('page.show', $page->slug))
            ->assertOk()
            ->assertSee('--content-scale: '.round(15 / 18, 4).';', false);
    }

    public function test_home_page_exposes_the_chosen_scale(): void
    {
        Setting::set(ContentTypography::SETTING_KEY, 20);

        $this->get('/')
            ->assertOk()
            ->assertSee('--content-scale: '.round(20 / 18, 4).';', false);
    }

    public function test_content_styles_multiply_every_size_by_the_scale(): void
    {
        $page = StaticPage::factory()->create([
            'blocks' => [
                ['type' => 'rich_text', 'content' => '<p>Isi halaman.</p>'],
            ],
        ]);

        $this->get(route('page.show', $page->slug))
            ->assertOk()
            ->assertSee('font-size: calc(1.0625rem * var(--content-scale, 1))', false)
            ->assertSee('font-size: calc(1.125rem * var(--content-scale, 1))', false);
    }
}
