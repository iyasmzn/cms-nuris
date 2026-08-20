<?php

namespace Tests\Feature\Filament;

use App\Filament\Pages\NavbarSettings;
use App\Models\Setting;
use App\Models\User;
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
}
