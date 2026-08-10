<?php

namespace Tests\Feature\Filament;

use App\Filament\Pages\LandingPageSettings;
use App\Models\ContentSection;
use App\Models\Media;
use App\Models\Setting;
use App\Models\User;
use App\Support\AlumniMarquee;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class LandingPageSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Permission::firstOrCreate(['name' => 'View:LandingPageSettings', 'guard_name' => 'web']);

        Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web'])
            ->syncPermissions(Permission::all());

        Role::firstOrCreate(['name' => 'author', 'guard_name' => 'web']);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function test_super_admin_can_open_the_page(): void
    {
        $admin = User::factory()->create()->assignRole('super_admin');

        $this->actingAs($admin)
            ->get(LandingPageSettings::getUrl())
            ->assertSuccessful();
    }

    public function test_author_cannot_open_the_page(): void
    {
        $author = User::factory()->create()->assignRole('author');

        $this->actingAs($author)
            ->get(LandingPageSettings::getUrl())
            ->assertForbidden();
    }

    public function test_it_persists_section_content_and_seo_settings(): void
    {
        $admin = User::factory()->create()->assignRole('super_admin');

        $component = Livewire::actingAs($admin)->test(LandingPageSettings::class);

        $component
            ->fillForm([
                'home_meta_title' => 'Pesantren Modern Terbaik',
                'home_meta_description' => 'Deskripsi meta khusus halaman depan.',
                'sections' => $this->withSectionState($component, 'section_programs', [
                    'eyebrow' => 'Program Kami',
                    'title' => 'Program Andalan Pesantren',
                    'subtitle' => 'Deskripsi program yang sudah diubah.',
                ]),
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('Pesantren Modern Terbaik', Setting::get('home_meta_title'));
        $this->assertSame('Deskripsi meta khusus halaman depan.', Setting::get('home_meta_description'));
        $this->assertSame('Program Kami', Setting::get('section_programs_eyebrow'));
        $this->assertSame('Program Andalan Pesantren', Setting::get('section_programs_title'));
        $this->assertSame('Deskripsi program yang sudah diubah.', Setting::get('section_programs_subtitle'));
    }

    public function test_it_persists_a_section_specific_extra_text(): void
    {
        $admin = User::factory()->create()->assignRole('super_admin');

        $component = Livewire::actingAs($admin)->test(LandingPageSettings::class);

        $component
            ->fillForm([
                'sections' => $this->withSectionState($component, 'section_alumni', [
                    'extra_logos_title' => 'Kampus Pilihan Lulusan',
                ]),
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('Kampus Pilihan Lulusan', Setting::get('section_alumni_logos_title'));
    }

    public function test_it_persists_the_background_settings_of_a_built_in_section(): void
    {
        $admin = User::factory()->create()->assignRole('super_admin');
        $background = Media::factory()->create(['path' => 'media/latar-seksi.jpg']);

        $component = Livewire::actingAs($admin)->test(LandingPageSettings::class);

        $component
            ->fillForm([
                'sections' => $this->withSectionState($component, 'section_stats', [
                    'background' => 'image',
                    'background_image_source' => 'library',
                    'background_image_library' => $background->id,
                    'background_blur' => 8,
                    'background_overlay' => 45,
                    'background_light_text' => true,
                    'background_parallax_mode' => 'scroll',
                    'background_parallax_speed' => 50,
                ]),
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('image', Setting::get('section_stats_background'));
        $this->assertSame('media/latar-seksi.jpg', Setting::get('section_stats_background_image'));
        $this->assertSame('8', (string) Setting::get('section_stats_background_blur'));
        $this->assertSame('45', (string) Setting::get('section_stats_background_overlay'));
        $this->assertSame('scroll', Setting::get('section_stats_background_parallax_mode'));
        $this->assertSame('50', (string) Setting::get('section_stats_background_parallax_speed'));
        $this->assertTrue(setting_bool('section_stats_background_light_text'));
    }

    public function test_the_saved_background_is_shown_again_when_the_page_is_reopened(): void
    {
        $admin = User::factory()->create()->assignRole('super_admin');

        Setting::setMany([
            'section_blog_background' => 'alt',
            'section_blog_background_blur' => 16,
        ]);

        $sections = collect(Livewire::actingAs($admin)->test(LandingPageSettings::class)->get('data.sections'));
        $blog = $sections->firstWhere('key', 'section_blog');

        $this->assertSame('alt', $blog['background']);
        $this->assertSame(16, (int) $blog['background_blur']);
    }

    public function test_the_hero_section_keeps_no_background_settings(): void
    {
        $admin = User::factory()->create()->assignRole('super_admin');

        Livewire::actingAs($admin)
            ->test(LandingPageSettings::class)
            ->call('save')
            ->assertHasNoFormErrors();

        // Latar hero diatur lewat slidernya sendiri, bukan halaman ini
        $this->assertNull(Setting::get('section_hero_background'));
        $this->assertNull(Setting::get('section_hero_background_image'));
        $this->assertSame('default', Setting::get('section_stats_background'));
    }

    public function test_it_persists_the_hero_slider_behaviour(): void
    {
        $admin = User::factory()->create()->assignRole('super_admin');

        $component = Livewire::actingAs($admin)->test(LandingPageSettings::class);

        $component
            ->fillForm([
                'sections' => $this->withSectionState($component, 'section_hero', [
                    'hero_autoplay' => true,
                    'hero_pause_on_hover' => false,
                    'hero_interval' => 9,
                    'hero_transition' => 'slide',
                    'hero_transition_duration' => 400,
                ]),
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertTrue(setting_bool('hero_autoplay'));
        $this->assertFalse(setting_bool('hero_pause_on_hover', true));
        $this->assertSame('9', (string) Setting::get('hero_interval'));
        $this->assertSame('slide', Setting::get('hero_transition'));
        $this->assertSame('400', (string) Setting::get('hero_transition_duration'));
    }

    public function test_the_saved_hero_slider_behaviour_is_shown_again_when_the_page_is_reopened(): void
    {
        $admin = User::factory()->create()->assignRole('super_admin');

        Setting::setMany([
            'hero_pause_on_hover' => false,
            'hero_interval' => 12,
            'hero_transition' => 'zoom',
        ]);

        $sections = collect(Livewire::actingAs($admin)->test(LandingPageSettings::class)->get('data.sections'));
        $hero = $sections->firstWhere('key', 'section_hero');

        $this->assertFalse($hero['hero_pause_on_hover']);
        $this->assertSame(12, (int) $hero['hero_interval']);
        $this->assertSame('zoom', $hero['hero_transition']);
    }

    public function test_it_persists_the_alumni_logo_row_behaviour(): void
    {
        $admin = User::factory()->create()->assignRole('super_admin');

        $component = Livewire::actingAs($admin)->test(LandingPageSettings::class);

        $component
            ->fillForm([
                'sections' => $this->withSectionState($component, 'section_alumni', [
                    'alumni_autoplay' => true,
                    'alumni_pause_on_hover' => false,
                    'alumni_speed' => 45,
                    'alumni_direction' => 'right',
                    'alumni_logo_height' => 96,
                    'alumni_card_width' => 260,
                    'alumni_gap' => 8,
                    'alumni_grayscale' => false,
                    'alumni_show_name' => false,
                ]),
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertTrue(setting_bool('alumni_marquee_autoplay'));
        $this->assertFalse(setting_bool('alumni_marquee_pause_on_hover', true));
        $this->assertSame('45', (string) Setting::get('alumni_marquee_speed'));
        $this->assertSame('right', Setting::get('alumni_marquee_direction'));
        $this->assertSame('96', (string) Setting::get('alumni_marquee_logo_height'));
        $this->assertSame('260', (string) Setting::get('alumni_marquee_card_width'));
        $this->assertSame('8', (string) Setting::get('alumni_marquee_gap'));
        $this->assertFalse(setting_bool('alumni_marquee_grayscale', true));
        $this->assertFalse(setting_bool('alumni_marquee_show_name', true));
    }

    public function test_the_saved_alumni_logo_row_behaviour_is_shown_again_when_the_page_is_reopened(): void
    {
        $admin = User::factory()->create()->assignRole('super_admin');

        Setting::setMany([
            'alumni_marquee_autoplay' => false,
            'alumni_marquee_speed' => 60,
            'alumni_marquee_direction' => 'right',
            'alumni_marquee_card_width' => 300,
        ]);

        $sections = collect(Livewire::actingAs($admin)->test(LandingPageSettings::class)->get('data.sections'));
        $alumni = $sections->firstWhere('key', 'section_alumni');

        $this->assertFalse($alumni['alumni_autoplay']);
        $this->assertSame(60, (int) $alumni['alumni_speed']);
        $this->assertSame('right', $alumni['alumni_direction']);
        $this->assertSame(300, (int) $alumni['alumni_card_width']);
    }

    /**
     * Repeater Filament memberi setiap barisnya kumpulan kolom yang sama, jadi
     * yang menentukan nilainya tersimpan adalah kunci seksinya — bukan kolom
     * mana saja yang kebetulan ada di state baris lain.
     */
    public function test_only_the_alumni_section_stores_logo_row_settings(): void
    {
        $admin = User::factory()->create()->assignRole('super_admin');

        $component = Livewire::actingAs($admin)->test(LandingPageSettings::class);

        $component
            ->fillForm([
                'sections' => $this->withSectionState($component, 'section_blog', [
                    'alumni_speed' => 99,
                ]),
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame((string) AlumniMarquee::DEFAULT_SPEED, (string) Setting::get('alumni_marquee_speed'));
    }

    public function test_it_keeps_section_order_and_visibility_when_saving_content(): void
    {
        $admin = User::factory()->create()->assignRole('super_admin');

        $component = Livewire::actingAs($admin)->test(LandingPageSettings::class);

        $component
            ->fillForm([
                'sections' => $this->withSectionState($component, 'section_blog', [
                    'title' => 'Kabar Terbaru',
                ]),
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $order = json_decode(Setting::get('section_order'), true);

        $this->assertIsArray($order);
        $this->assertContains('section_programs', array_column($order, 'key'));
        $this->assertContains('section_blog', array_column($order, 'key'));
        $this->assertSame('Kabar Terbaru', Setting::get('section_blog_title'));
    }

    public function test_dragging_a_section_persists_the_new_order(): void
    {
        $admin = User::factory()->create()->assignRole('super_admin');

        $component = Livewire::actingAs($admin)->test(LandingPageSettings::class);

        // Pindahkan FAQ ke urutan paling atas, seperti hasil drag-and-drop
        $sections = collect($component->get('data.sections'));
        $faq = $sections->firstWhere('key', 'section_faq');
        $reordered = $sections->reject(fn (array $entry): bool => $entry['key'] === 'section_faq')
            ->prepend($faq)
            ->values()
            ->all();

        $component
            ->fillForm(['sections' => $reordered])
            ->call('save')
            ->assertHasNoFormErrors();

        $order = json_decode(Setting::get('section_order'), true);

        $this->assertSame('section_faq', $order[0]['key']);
    }

    public function test_dynamic_content_sections_join_the_order_list(): void
    {
        $admin = User::factory()->create()->assignRole('super_admin');
        $section = ContentSection::factory()->create(['title' => 'Fasilitas Kami']);

        Livewire::actingAs($admin)
            ->test(LandingPageSettings::class)
            ->call('save')
            ->assertHasNoFormErrors();

        $order = json_decode(Setting::get('section_order'), true);

        $this->assertContains($section->order_key, array_column($order, 'key'));
    }

    public function test_the_visibility_toggle_syncs_a_dynamic_section_publication_status(): void
    {
        $admin = User::factory()->create()->assignRole('super_admin');
        $section = ContentSection::factory()->create(['is_published' => true]);

        $component = Livewire::actingAs($admin)->test(LandingPageSettings::class);

        $sections = collect($component->get('data.sections'))
            ->map(function (array $entry) use ($section): array {
                if ($entry['key'] === $section->order_key) {
                    $entry['visible'] = false;
                }

                return $entry;
            })
            ->all();

        $component->fillForm(['sections' => $sections])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertFalse($section->fresh()->is_published);
    }

    /**
     * ID anchor yang ditampilkan di panel harus sama persis dengan atribut `id`
     * pada partial Blade seksinya — kalau tidak, tautan menu yang dibuat admin
     * dari situ tidak akan menemukan tujuannya. Partial-nya diperiksa langsung
     * sebab sebagian seksi hanya tampil bila datanya ada.
     */
    public function test_every_section_row_carries_the_anchor_id_declared_by_its_partial(): void
    {
        $admin = User::factory()->create()->assignRole('super_admin');
        $section = ContentSection::factory()->create([
            'is_published' => true,
            'anchor' => 'Fasilitas Kami',
        ]);

        $anchors = collect(
            Livewire::actingAs($admin)->test(LandingPageSettings::class)->get('data.sections')
        )->pluck('anchor', 'key');

        $this->assertSame('fasilitas-kami', $anchors[$section->order_key]);

        foreach ($anchors as $key => $anchor) {
            $this->assertNotEmpty($anchor, "Seksi {$key} tidak punya ID anchor.");

            if ($key === $section->order_key) {
                continue;
            }

            $partial = resource_path(
                'views/sections/'.str_replace(['section_', '_'], ['', '-'], $key).'.blade.php'
            );

            $this->assertFileExists($partial);
            $this->assertStringContainsString(
                'id="'.$anchor.'"',
                (string) file_get_contents($partial),
                "ID anchor #{$anchor} tidak ada di partial seksi {$key}.",
            );
        }
    }

    public function test_a_dynamic_section_renders_the_anchor_id_shown_in_the_panel(): void
    {
        $section = ContentSection::factory()->create([
            'is_published' => true,
            'anchor' => 'Fasilitas Kami',
        ]);

        $this->get('/')
            ->assertSuccessful()
            ->assertSee('id="fasilitas-kami"', false);
    }

    /**
     * Seksi kini diurutkan sekaligus diubah teksnya dalam satu repeater, jadi
     * mengisi satu kolom berarti mengirim ulang seluruh state daftarnya.
     *
     * @param  array<string, mixed>  $changes
     * @return array<int, array<string, mixed>>
     */
    private function withSectionState(Testable $component, string $key, array $changes): array
    {
        return collect($component->get('data.sections'))
            ->map(fn (array $entry): array => $entry['key'] === $key
                ? [...$entry, ...$changes]
                : $entry)
            ->values()
            ->all();
    }
}
