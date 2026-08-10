<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\StaticPage;
use App\Support\PageWidth;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Lebar maksimum isi halaman diatur sekali lalu diteruskan ke stylesheet lewat
 * custom property `--page-max-width`.
 */
class PageWidthTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_defaults_to_the_original_design_width(): void
    {
        $this->assertSame(PageWidth::DEFAULT_WIDTH, PageWidth::key());
        $this->assertSame(1280, PageWidth::pixels());
        $this->assertStringContainsString(':root { --page-max-width: 80rem; }', PageWidth::css());
    }

    public function test_it_reads_the_saved_width(): void
    {
        Setting::set(PageWidth::SETTING_KEY, '5xl');

        $this->assertSame('5xl', PageWidth::key());
        $this->assertSame(1024, PageWidth::pixels());
        $this->assertStringContainsString(':root { --page-max-width: 64rem; }', PageWidth::css());
    }

    public function test_full_width_removes_the_cap(): void
    {
        Setting::set(PageWidth::SETTING_KEY, 'full');

        $this->assertNull(PageWidth::pixels());
        $this->assertStringContainsString(':root { --page-max-width: none; }', PageWidth::css());
    }

    public function test_it_falls_back_to_the_default_for_an_unknown_width(): void
    {
        Setting::set(PageWidth::SETTING_KEY, '99xl');

        $this->assertSame(PageWidth::DEFAULT_WIDTH, PageWidth::key());
        $this->assertSame(PageWidth::DEFAULT_WIDTH, PageWidth::sanitize(null));
        $this->assertSame(PageWidth::DEFAULT_WIDTH, PageWidth::sanitize(1280));
    }

    public function test_css_applies_the_cap_to_the_standard_container(): void
    {
        $this->assertStringContainsString(
            '.max-w-7xl.mx-auto { max-width: var(--page-max-width); }',
            PageWidth::css()
        );
    }

    public function test_public_layout_exposes_the_chosen_width(): void
    {
        Setting::set(PageWidth::SETTING_KEY, 'wide');

        $page = StaticPage::factory()->create([
            'blocks' => [
                ['type' => 'rich_text', 'content' => '<p>Isi halaman.</p>'],
            ],
        ]);

        $this->get(route('page.show', $page->slug))
            ->assertOk()
            ->assertSee(':root { --page-max-width: 90rem; }', false);
    }

    public function test_home_page_exposes_the_chosen_width(): void
    {
        Setting::set(PageWidth::SETTING_KEY, 'full');

        $this->get('/')
            ->assertOk()
            ->assertSee(':root { --page-max-width: none; }', false);
    }
}
