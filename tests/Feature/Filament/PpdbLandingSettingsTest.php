<?php

namespace Tests\Feature\Filament;

use App\Filament\Pages\PpdbLandingSettings;
use App\Models\Institution;
use App\Models\Setting;
use App\Models\User;
use App\Support\PpdbLanding;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class PpdbLandingSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Permission::firstOrCreate(['name' => 'View:PpdbLandingSettings', 'guard_name' => 'web']);

        Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web'])
            ->syncPermissions(Permission::all());

        Role::firstOrCreate(['name' => 'author', 'guard_name' => 'web']);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function test_super_admin_can_open_the_page(): void
    {
        $admin = User::factory()->create()->assignRole('super_admin');

        $this->actingAs($admin)
            ->get(PpdbLandingSettings::getUrl())
            ->assertSuccessful()
            ->assertSee('Seksi Halaman');
    }

    public function test_author_cannot_open_the_page(): void
    {
        $author = User::factory()->create()->assignRole('author');

        $this->actingAs($author)
            ->get(PpdbLandingSettings::getUrl())
            ->assertForbidden();
    }

    public function test_page_opens_with_the_default_layout(): void
    {
        $admin = User::factory()->create()->assignRole('super_admin');

        $page = Livewire::actingAs($admin)
            ->test(PpdbLandingSettings::class)
            ->assertFormSet([
                'hero.title' => 'Penerimaan Peserta Didik Baru',
                'hero.primary_url' => '#jenjang',
            ]);

        $this->assertSame(
            array_column(PpdbLanding::defaultSections(), 'type'),
            array_column(array_values($page->get('data.blocks')), 'type'),
        );
    }

    public function test_it_saves_the_hero_and_sections(): void
    {
        $admin = User::factory()->create()->assignRole('super_admin');
        $institution = Institution::factory()->create();

        Livewire::actingAs($admin)
            ->test(PpdbLandingSettings::class)
            ->fillForm([
                'hero.title' => 'Selamat Datang di Platform Pendaftaran',
                'hero.primary_label' => 'Daftar Sekarang',
                'hero.primary_url' => '#jalur',
                'blocks' => [
                    ['type' => 'ppdb_paths', 'heading' => 'Jalur Penerimaan', 'intro' => 'Beragam jalur.', 'anchor' => 'jalur', 'paths_layout' => 'list', 'paths_columns' => 2],
                    ['type' => 'ppdb_procedures', 'heading' => 'Alur SMA', 'institution_id' => $institution->id, 'procedures_columns' => 3],
                    ['type' => 'ppdb_institutions', 'heading' => 'Jenjang', 'institutions_columns' => PpdbLanding::AUTO_COLUMNS],
                    ['type' => 'rich_text', 'heading' => 'Tentang Kami', 'content' => '<p>Sekolah berasrama.</p>'],
                    [
                        'type' => 'ppdb_timeline',
                        'heading' => 'Tanggal Penting',
                        'timeline_layout' => 'horizontal',
                        'timeline_items' => [
                            ['icon' => '📝', 'label' => 'Januari', 'title' => 'Pendaftaran', 'description' => 'Isi formulir.', 'highlighted' => true],
                            ['icon' => '', 'label' => 'Februari', 'title' => 'Tes Seleksi', 'description' => '', 'highlighted' => false],
                        ],
                    ],
                ],
                'meta_description' => 'Pendaftaran dibuka.',
            ])
            ->call('save')
            ->assertHasNoFormErrors()
            ->assertNotified();

        $hero = json_decode((string) Setting::get(PpdbLanding::HERO_SETTING), true);
        $sections = json_decode((string) Setting::get(PpdbLanding::SECTIONS_SETTING), true);

        $this->assertSame('Selamat Datang di Platform Pendaftaran', $hero['title']);
        $this->assertSame('#jalur', $hero['primary_url']);
        $this->assertSame(['ppdb_paths', 'ppdb_procedures', 'ppdb_institutions', 'rich_text', 'ppdb_timeline'], array_column($sections, 'type'));
        $this->assertSame('horizontal', $sections[4]['timeline_layout']);
        $this->assertSame(['Pendaftaran', 'Tes Seleksi'], array_column(array_values($sections[4]['timeline_items']), 'title'));
        $this->assertTrue(array_values($sections[4]['timeline_items'])[0]['highlighted']);
        $this->assertSame('Beragam jalur.', $sections[0]['intro']);
        $this->assertSame('list', $sections[0]['paths_layout']);
        $this->assertEquals(2, $sections[0]['paths_columns']);
        $this->assertEquals($institution->id, $sections[1]['institution_id']);
        $this->assertEquals(3, $sections[1]['procedures_columns']);
        $this->assertSame(PpdbLanding::AUTO_COLUMNS, $sections[2]['institutions_columns']);
        $this->assertSame('Pendaftaran dibuka.', Setting::get(PpdbLanding::META_SETTING));
        $this->assertTrue(PpdbLanding::isCustomized());
    }

    public function test_timeline_points_need_a_title(): void
    {
        $admin = User::factory()->create()->assignRole('super_admin');

        Livewire::actingAs($admin)
            ->test(PpdbLandingSettings::class)
            ->fillForm([
                'blocks' => [
                    ['type' => 'ppdb_timeline', 'heading' => 'Tanggal Penting', 'timeline_items' => [['label' => 'Januari', 'title' => '']]],
                ],
            ])
            ->call('save')
            ->assertHasErrors();
    }

    public function test_hero_button_links_must_look_like_links(): void
    {
        $admin = User::factory()->create()->assignRole('super_admin');

        Livewire::actingAs($admin)
            ->test(PpdbLandingSettings::class)
            ->fillForm(['hero.primary_url' => 'javascript:alert(1)'])
            ->call('save')
            ->assertHasFormErrors(['hero.primary_url' => 'regex']);
    }

    public function test_reset_restores_the_default_layout(): void
    {
        $admin = User::factory()->create()->assignRole('super_admin');
        Setting::set(PpdbLanding::SECTIONS_SETTING, json_encode([['type' => 'ppdb_faq', 'heading' => 'FAQ']]));
        Setting::set(PpdbLanding::HERO_SETTING, json_encode(['title' => 'Judul Lama']));

        Livewire::actingAs($admin)
            ->test(PpdbLandingSettings::class)
            ->callAction('reset')
            ->assertFormSet(['hero.title' => 'Penerimaan Peserta Didik Baru']);

        $this->assertFalse(PpdbLanding::isCustomized());
        $this->assertNull(Setting::get(PpdbLanding::HERO_SETTING));
    }
}
