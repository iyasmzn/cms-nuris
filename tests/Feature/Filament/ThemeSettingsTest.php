<?php

namespace Tests\Feature\Filament;

use App\Filament\Pages\ThemeSettings;
use App\Models\Setting;
use App\Models\User;
use App\Support\ContentTypography;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class ThemeSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Permission::firstOrCreate(['name' => 'View:ThemeSettings', 'guard_name' => 'web']);

        Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web'])
            ->syncPermissions(Permission::all());

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function test_it_persists_the_content_font_size(): void
    {
        $admin = User::factory()->create()->assignRole('super_admin');

        Livewire::actingAs($admin)
            ->test(ThemeSettings::class)
            ->assertFormSet(['content_font_size' => ContentTypography::DEFAULT_SIZE])
            ->fillForm(['content_font_size' => 16])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame(16, ContentTypography::size());
    }

    public function test_reset_restores_the_default_content_font_size(): void
    {
        Setting::set(ContentTypography::SETTING_KEY, 20);

        $admin = User::factory()->create()->assignRole('super_admin');

        Livewire::actingAs($admin)
            ->test(ThemeSettings::class)
            ->callAction('reset');

        $this->assertSame(ContentTypography::DEFAULT_SIZE, ContentTypography::size());
    }
}
