<?php

namespace Tests\Feature\Filament;

use App\Filament\Pages\SpmbSettings;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class SpmbSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Permission::firstOrCreate(['name' => 'View:SpmbSettings', 'guard_name' => 'web']);

        Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web'])
            ->syncPermissions(Permission::all());

        Role::firstOrCreate(['name' => 'author', 'guard_name' => 'web']);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function test_super_admin_can_open_the_page(): void
    {
        $admin = User::factory()->create()->assignRole('super_admin');

        $this->actingAs($admin)
            ->get(SpmbSettings::getUrl())
            ->assertSuccessful();
    }

    public function test_author_cannot_open_the_page(): void
    {
        $author = User::factory()->create()->assignRole('author');

        $this->actingAs($author)
            ->get(SpmbSettings::getUrl())
            ->assertForbidden();
    }

    public function test_it_persists_document_requirements(): void
    {
        $admin = User::factory()->create()->assignRole('super_admin');

        Livewire::actingAs($admin)
            ->test(SpmbSettings::class)
            ->fillForm([
                'requirements' => [
                    ['requirement' => 'Fotokopi Kartu Keluarga'],
                    ['requirement' => 'Pas foto 3x4'],
                ],
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame(
            ['Fotokopi Kartu Keluarga', 'Pas foto 3x4'],
            json_decode((string) Setting::get('spmb_requirements'), true),
        );
    }

    /**
     * The simple repeater stores a flat list of strings; make sure that shape
     * survives a mount → save round trip so opening the page and pressing save
     * never silently drops the saved persyaratan.
     */
    public function test_saved_document_requirements_survive_a_round_trip(): void
    {
        Setting::set('spmb_requirements', json_encode(['Fotokopi Kartu Keluarga', 'Pas foto 3x4']));

        $admin = User::factory()->create()->assignRole('super_admin');

        Livewire::actingAs($admin)
            ->test(SpmbSettings::class)
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame(
            ['Fotokopi Kartu Keluarga', 'Pas foto 3x4'],
            json_decode((string) Setting::get('spmb_requirements'), true),
        );
    }

    public function test_it_persists_the_cash_payment_option(): void
    {
        $admin = User::factory()->create()->assignRole('super_admin');

        Livewire::actingAs($admin)
            ->test(SpmbSettings::class)
            ->fillForm([
                'spmb_payment_enabled' => true,
                'spmb_payment_cash_enabled' => true,
                'spmb_payment_cash_instructions' => 'Kantor TU, Senin-Sabtu 08.00-14.00.',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertTrue(setting_bool('spmb_payment_cash_enabled'));
        $this->assertSame('Kantor TU, Senin-Sabtu 08.00-14.00.', Setting::get('spmb_payment_cash_instructions'));
    }

    /**
     * The cash fields hide while no jenjang charges a fee. Saving the page in
     * that state must not reset them.
     */
    public function test_the_cash_option_survives_a_save_while_tagihan_are_switched_off(): void
    {
        Setting::setMany([
            'spmb_payment_enabled' => '0',
            'spmb_payment_cash_enabled' => '1',
            'spmb_payment_cash_instructions' => 'Kantor TU.',
        ]);

        $admin = User::factory()->create()->assignRole('super_admin');

        Livewire::actingAs($admin)
            ->test(SpmbSettings::class)
            ->assertFormFieldHidden('spmb_payment_cash_enabled')
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertTrue(setting_bool('spmb_payment_cash_enabled'));
        $this->assertSame('Kantor TU.', Setting::get('spmb_payment_cash_instructions'));
    }

    public function test_document_requirements_can_be_emptied(): void
    {
        Setting::set('spmb_requirements', json_encode(['Fotokopi Kartu Keluarga']));

        $admin = User::factory()->create()->assignRole('super_admin');

        Livewire::actingAs($admin)
            ->test(SpmbSettings::class)
            ->fillForm(['requirements' => []])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame([], json_decode((string) Setting::get('spmb_requirements'), true));
    }
}
