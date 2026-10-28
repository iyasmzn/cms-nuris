<?php

namespace Tests\Feature\Filament;

use App\Filament\Pages\ThemeSettings;
use App\Models\Setting;
use App\Models\User;
use App\Support\ContentTypography;
use App\Support\HeroPattern;
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

    public function test_it_persists_the_hero_pattern(): void
    {
        Livewire::actingAs(User::factory()->create()->assignRole('super_admin'))
            ->test(ThemeSettings::class)
            ->fillForm([
                'hero_pattern' => 'diamonds',
                'hero_pattern_opacity' => 14,
                'hero_pattern_scale' => 75,
                'hero_pattern_color' => 'gold',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('diamonds', Setting::get('hero_pattern'));
        $this->assertSame('14', (string) Setting::get('hero_pattern_opacity'));
        $this->assertSame('75', (string) Setting::get('hero_pattern_scale'));
        $this->assertSame('gold', Setting::get('hero_pattern_color'));
    }

    public function test_the_hero_pattern_defaults_to_the_eight_pointed_star(): void
    {
        Livewire::actingAs(User::factory()->create()->assignRole('super_admin'))
            ->test(ThemeSettings::class)
            ->assertFormSet([
                'hero_pattern' => HeroPattern::DEFAULT_PATTERN,
                'hero_pattern_opacity' => HeroPattern::DEFAULT_OPACITY,
                'hero_pattern_scale' => HeroPattern::DEFAULT_SCALE,
                'hero_pattern_color' => HeroPattern::DEFAULT_COLOR,
            ]);

        $this->assertSame('khatam', HeroPattern::DEFAULT_PATTERN);
    }

    public function test_the_hero_pattern_details_only_appear_once_a_pattern_is_chosen(): void
    {
        Livewire::actingAs(User::factory()->create()->assignRole('super_admin'))
            ->test(ThemeSettings::class)
            ->fillForm(['hero_pattern' => 'none'])
            ->assertFormFieldHidden('hero_pattern_opacity')
            ->assertFormFieldHidden('hero_pattern_color')
            ->fillForm(['hero_pattern' => 'dots'])
            ->assertFormFieldVisible('hero_pattern_opacity')
            ->assertFormFieldVisible('hero_pattern_color')
            ->assertFormFieldHidden('hero_pattern_custom_color')
            ->fillForm(['hero_pattern_color' => 'custom'])
            ->assertFormFieldVisible('hero_pattern_custom_color');
    }

    /**
     * Switching the pattern off for a while must not throw away the opacity,
     * tile size and colours the admin had set for it.
     */
    public function test_the_hero_pattern_details_survive_while_the_pattern_is_off(): void
    {
        Setting::setMany([
            'hero_pattern' => 'diamonds',
            'hero_pattern_opacity' => 22,
            'hero_pattern_scale' => 200,
            'hero_pattern_color' => 'custom',
            'hero_pattern_custom_color' => '#ffcc00',
        ]);

        Livewire::actingAs(User::factory()->create()->assignRole('super_admin'))
            ->test(ThemeSettings::class)
            ->fillForm(['hero_pattern' => 'none'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('none', Setting::get('hero_pattern'));
        $this->assertSame('22', (string) Setting::get('hero_pattern_opacity'));
        $this->assertSame('200', (string) Setting::get('hero_pattern_scale'));
        $this->assertSame('custom', Setting::get('hero_pattern_color'));
        $this->assertSame('#ffcc00', Setting::get('hero_pattern_custom_color'));
    }

    public function test_reset_restores_the_default_hero_pattern(): void
    {
        Setting::setMany(['hero_pattern' => 'none', 'hero_pattern_color' => 'gold']);

        Livewire::actingAs(User::factory()->create()->assignRole('super_admin'))
            ->test(ThemeSettings::class)
            ->callAction('reset');

        $this->assertSame(HeroPattern::DEFAULT_PATTERN, Setting::get('hero_pattern'));
        $this->assertSame(HeroPattern::DEFAULT_COLOR, Setting::get('hero_pattern_color'));
    }
}
