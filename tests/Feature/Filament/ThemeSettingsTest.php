<?php

namespace Tests\Feature\Filament;

use App\Filament\Pages\ThemeSettings;
use App\Models\Setting;
use App\Models\User;
use App\Support\ContentTypography;
use App\Support\PageGutter;
use App\Support\PageWidth;
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

    public function test_it_persists_the_page_gutter_of_each_breakpoint(): void
    {
        $admin = User::factory()->create()->assignRole('super_admin');

        Livewire::actingAs($admin)
            ->test(ThemeSettings::class)
            ->assertFormSet([PageGutter::settingKey('lg') => 32])
            ->fillForm([
                PageGutter::settingKey('mobile') => 12,
                PageGutter::settingKey('lg') => 64,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $values = PageGutter::values();

        $this->assertSame(12, $values['mobile']);
        $this->assertSame(64, $values['lg']);
    }

    public function test_it_rejects_a_page_gutter_beyond_the_allowed_range(): void
    {
        $admin = User::factory()->create()->assignRole('super_admin');

        Livewire::actingAs($admin)
            ->test(ThemeSettings::class)
            ->fillForm([PageGutter::settingKey('mobile') => PageGutter::MAX + 10])
            ->call('save')
            ->assertHasFormErrors([PageGutter::settingKey('mobile') => 'max']);

        $this->assertSame(16, PageGutter::values()['mobile']);
    }

    public function test_reset_restores_the_default_page_gutters(): void
    {
        Setting::set(PageGutter::settingKey('mobile'), 4);

        $admin = User::factory()->create()->assignRole('super_admin');

        Livewire::actingAs($admin)
            ->test(ThemeSettings::class)
            ->callAction('reset');

        $this->assertSame(PageGutter::defaults(), PageGutter::values());
    }

    public function test_it_persists_the_page_max_width(): void
    {
        $admin = User::factory()->create()->assignRole('super_admin');

        Livewire::actingAs($admin)
            ->test(ThemeSettings::class)
            ->assertFormSet([PageWidth::SETTING_KEY => PageWidth::DEFAULT_WIDTH])
            ->fillForm([PageWidth::SETTING_KEY => 'full'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('full', PageWidth::key());
    }

    public function test_reset_restores_the_default_page_max_width(): void
    {
        Setting::set(PageWidth::SETTING_KEY, '4xl');

        $admin = User::factory()->create()->assignRole('super_admin');

        Livewire::actingAs($admin)
            ->test(ThemeSettings::class)
            ->callAction('reset');

        $this->assertSame(PageWidth::DEFAULT_WIDTH, PageWidth::key());
    }
}
