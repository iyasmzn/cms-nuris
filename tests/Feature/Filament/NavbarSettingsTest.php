<?php

namespace Tests\Feature\Filament;

use App\Filament\Pages\NavbarSettings;
use App\Models\Setting;
use App\Models\User;
use App\Support\NavHighlight;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class NavbarSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Permission::firstOrCreate(['name' => 'View:NavbarSettings', 'guard_name' => 'web']);

        Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web'])
            ->syncPermissions(Permission::all());

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /**
     * The dropdown can show an icon and a line of explanation per sub-menu, so
     * both have to survive the round trip through the `nav_items` setting.
     */
    public function test_it_saves_the_icon_and_description_of_a_submenu_entry(): void
    {
        $admin = User::factory()->create()->assignRole('super_admin');

        Livewire::actingAs($admin)
            ->test(NavbarSettings::class)
            ->fillForm([
                'items' => [
                    [
                        'label' => 'Akademik',
                        'url' => '#akademik',
                        'target' => '_self',
                        'is_active' => true,
                        'children' => [
                            [
                                'icon' => '📘',
                                'label' => 'Kurikulum',
                                'url' => '#kurikulum',
                                'target' => '_self',
                                'description' => 'Struktur & jadwal pelajaran',
                                'is_active' => true,
                            ],
                        ],
                    ],
                ],
            ])
            ->call('save')
            ->assertHasNoFormErrors()
            ->assertNotified();

        $child = json_decode(Setting::get('nav_items'), true)[0]['children'][0];

        $this->assertSame('📘', $child['icon']);
        $this->assertSame('Struktur & jadwal pelajaran', $child['description']);
    }

    public function test_it_saves_the_appearance_chosen_for_an_item_and_its_submenu(): void
    {
        $admin = User::factory()->create()->assignRole('super_admin');

        Livewire::actingAs($admin)
            ->test(NavbarSettings::class)
            ->fillForm([
                'items' => [
                    [
                        'label' => 'Daftar Sekarang',
                        'url' => '/spmb',
                        'target' => '_self',
                        'is_active' => true,
                        'style' => 'button',
                        'text_color' => '#FFFFFF',
                        'bg_color' => '#E11D48',
                        'children' => [
                            [
                                'label' => 'Jalur Prestasi',
                                'url' => '/spmb/prestasi',
                                'target' => '_self',
                                'is_active' => true,
                                'text_color' => '#7c2d12',
                                'bg_color' => '#fef3c7',
                            ],
                        ],
                    ],
                ],
            ])
            ->call('save')
            ->assertHasNoFormErrors()
            ->assertNotified();

        $item = json_decode(Setting::get('nav_items'), true)[0];

        $this->assertSame('button', $item['style']);
        $this->assertSame('#ffffff', $item['text_color']);
        $this->assertSame('#e11d48', $item['bg_color']);
        $this->assertSame('#7c2d12', $item['children'][0]['text_color']);
        $this->assertSame('#fef3c7', $item['children'][0]['bg_color']);
    }

    /**
     * The style becomes a CSS class name when the navbar renders, so a value that
     * is not one of the offered styles has to be refused outright.
     */
    public function test_it_refuses_a_style_that_is_not_one_of_the_offered_ones(): void
    {
        $admin = User::factory()->create()->assignRole('super_admin');

        Livewire::actingAs($admin)
            ->test(NavbarSettings::class)
            ->fillForm([
                'items' => [
                    ['label' => 'Beranda', 'url' => '/', 'target' => '_self', 'is_active' => true, 'style' => 'nav-item-button" onclick="alert(1)', 'children' => []],
                ],
            ])
            ->call('save')
            ->assertHasFormErrors(['items.0.style']);

        $this->assertNull(Setting::get('nav_items'));
    }

    public function test_it_saves_the_menu_highlight_colour(): void
    {
        $admin = User::factory()->create()->assignRole('super_admin');

        Livewire::actingAs($admin)
            ->test(NavbarSettings::class)
            ->fillForm([NavHighlight::SETTING_KEY => '#E11D48'])
            ->call('save')
            ->assertHasNoFormErrors()
            ->assertNotified();

        $this->assertSame('#e11d48', Setting::get(NavHighlight::SETTING_KEY));
    }

    /**
     * The value lands in a stylesheet, so anything that is not a plain HEX has to
     * be refused rather than tidied up and let through.
     */
    public function test_it_rejects_a_highlight_colour_that_is_not_a_hex_code(): void
    {
        $admin = User::factory()->create()->assignRole('super_admin');

        Livewire::actingAs($admin)
            ->test(NavbarSettings::class)
            ->fillForm([NavHighlight::SETTING_KEY => 'red; } body { display: none'])
            ->call('save')
            ->assertHasFormErrors([NavHighlight::SETTING_KEY]);

        $this->assertNull(Setting::get(NavHighlight::SETTING_KEY));
    }

    /**
     * An empty colour is a real choice: follow the site's primary colour.
     */
    public function test_an_empty_highlight_colour_falls_back_to_the_primary_colour(): void
    {
        $admin = User::factory()->create()->assignRole('super_admin');

        Setting::set(NavHighlight::SETTING_KEY, '#e11d48');

        Livewire::actingAs($admin)
            ->test(NavbarSettings::class)
            ->fillForm([NavHighlight::SETTING_KEY => ''])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertNull(NavHighlight::color());
        $this->assertStringContainsString('--nav-highlight: var(--primary);', NavHighlight::css());
    }
}
