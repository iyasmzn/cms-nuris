<?php

namespace Tests\Feature\Filament;

use App\Filament\Pages\LandingPageSettings;
use App\Models\ContentSection;
use App\Models\Setting;
use App\Models\User;
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
