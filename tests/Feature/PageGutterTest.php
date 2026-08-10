<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\StaticPage;
use App\Support\PageGutter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Jarak tepi kiri & kanan halaman diatur sekali per ukuran layar lalu
 * diteruskan ke stylesheet lewat custom property `--page-gutter`.
 */
class PageGutterTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_defaults_to_the_original_design_spacing(): void
    {
        $this->assertSame(
            ['mobile' => 16, 'sm' => 24, 'md' => 24, 'lg' => 32, 'xl' => 32],
            PageGutter::values()
        );
    }

    public function test_it_reads_the_saved_value_for_each_breakpoint(): void
    {
        Setting::set(PageGutter::settingKey('mobile'), 8);
        Setting::set(PageGutter::settingKey('xl'), 96);

        $values = PageGutter::values();

        $this->assertSame(8, $values['mobile']);
        $this->assertSame(96, $values['xl']);
        $this->assertSame(24, $values['sm']);
    }

    public function test_it_clamps_out_of_range_values_and_falls_back_when_empty(): void
    {
        $this->assertSame(PageGutter::MAX, PageGutter::sanitize(999, 'lg'));
        $this->assertSame(PageGutter::MIN, PageGutter::sanitize(-40, 'lg'));
        $this->assertSame(32, PageGutter::sanitize('', 'lg'));
        $this->assertSame(32, PageGutter::sanitize('lebar', 'lg'));
        $this->assertSame(25, PageGutter::sanitize('24.6', 'lg'));
    }

    public function test_css_declares_a_base_value_and_one_media_query_per_breakpoint(): void
    {
        Setting::set(PageGutter::settingKey('mobile'), 12);
        Setting::set(PageGutter::settingKey('lg'), 64);

        $css = PageGutter::css();

        $this->assertStringContainsString(':root { --page-gutter: 12px; }', $css);
        $this->assertStringContainsString('@media (min-width: 1024px) { :root { --page-gutter: 64px; } }', $css);
        $this->assertStringContainsString(
            '.px-4.sm\:px-6.lg\:px-8 { padding-left: var(--page-gutter); padding-right: var(--page-gutter); }',
            $css
        );
    }

    public function test_public_layout_exposes_the_chosen_gutters(): void
    {
        Setting::set(PageGutter::settingKey('mobile'), 20);
        Setting::set(PageGutter::settingKey('md'), 48);

        $page = StaticPage::factory()->create([
            'blocks' => [
                ['type' => 'rich_text', 'content' => '<p>Isi halaman.</p>'],
            ],
        ]);

        $this->get(route('page.show', $page->slug))
            ->assertOk()
            ->assertSee(':root { --page-gutter: 20px; }', false)
            ->assertSee('@media (min-width: 768px) { :root { --page-gutter: 48px; } }', false);
    }

    public function test_home_page_exposes_the_chosen_gutters(): void
    {
        Setting::set(PageGutter::settingKey('xl'), 72);

        $this->get('/')
            ->assertOk()
            ->assertSee('@media (min-width: 1280px) { :root { --page-gutter: 72px; } }', false);
    }
}
