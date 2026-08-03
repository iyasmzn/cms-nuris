<?php

namespace Tests\Feature\Filament;

use App\Filament\Actions\SettlePaymentAction;
use App\Filament\Actions\UpdateRegistrationStatusAction;
use App\Filament\Resources\RegistrationPayments\Pages\ListRegistrationPayments;
use App\Filament\Resources\RegistrationPayments\Pages\ViewRegistrationPayment;
use App\Filament\Resources\RegistrationPayments\RegistrationPaymentResource;
use App\Filament\Resources\SpmbRegistrations\Pages\ListSpmbRegistrations;
use App\Filament\Resources\SpmbRegistrations\Pages\ViewSpmbRegistration;
use App\Filament\Resources\SpmbRegistrations\SpmbRegistrationResource;
use App\Models\Institution;
use App\Models\RegistrationPayment;
use App\Models\SpmbRegistration;
use App\Models\User;
use Database\Seeders\ShieldSeeder;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * The `verifikator_ppdb` role exists so panitia can decide on registrations and
 * bukti transfer without being able to alter the underlying data. These tests
 * pin both halves of that contract: what the role CAN do, and what it must not.
 */
class PpdbVerifierRoleTest extends TestCase
{
    use RefreshDatabase;

    private User $verifier;

    private SpmbRegistration $registration;

    private RegistrationPayment $payment;

    protected function setUp(): void
    {
        parent::setUp();

        $this->verifier = $this->verifierUser();

        $institution = Institution::factory()->create(['registration_fee' => 150_000]);
        $this->registration = SpmbRegistration::factory()->pending()->create([
            'institution_id' => $institution->id,
        ]);
        $this->payment = RegistrationPayment::factory()->waitingVerification()->create([
            'spmb_registration_id' => $this->registration->id,
            'amount' => 150_000,
        ]);

        $this->actingAs($this->verifier);
    }

    /**
     * Builds the role straight from the seeder's own list, so a permission
     * added or dropped there is reflected here instead of silently drifting.
     */
    private function verifierUser(): User
    {
        foreach (ShieldSeeder::verifierPermissions() as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $role = Role::findOrCreate('verifikator_ppdb', 'web');
        $role->syncPermissions(Permission::whereIn('name', ShieldSeeder::verifierPermissions())->get());

        $user = User::factory()->create();
        $user->assignRole($role);

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return $user;
    }

    // ── Yang boleh ───────────────────────────────────────────────────

    public function test_verifier_can_open_the_registration_list_and_preview(): void
    {
        Livewire::test(ListSpmbRegistrations::class)
            ->assertOk()
            ->assertCanSeeTableRecords([$this->registration]);

        Livewire::test(ViewSpmbRegistration::class, ['record' => $this->registration->id])
            ->assertOk();
    }

    public function test_verifier_can_open_the_payment_list_and_preview(): void
    {
        Livewire::test(ListRegistrationPayments::class)
            ->assertOk()
            ->assertCanSeeTableRecords([$this->payment]);

        Livewire::test(ViewRegistrationPayment::class, ['record' => $this->payment->id])
            ->assertOk();
    }

    public function test_verifier_can_change_a_registration_status(): void
    {
        Livewire::test(ViewSpmbRegistration::class, ['record' => $this->registration->id])
            ->callAction(UpdateRegistrationStatusAction::class, [
                'status' => 'accepted',
                'notes' => 'Berkas lengkap.',
            ])
            ->assertNotified();

        $this->registration->refresh();
        $this->assertSame('accepted', $this->registration->status);
        $this->assertSame('Berkas lengkap.', $this->registration->notes);
        $this->assertNotNull($this->registration->verified_at);
    }

    public function test_verifier_can_settle_a_payment_from_the_registration_preview(): void
    {
        Livewire::test(ViewSpmbRegistration::class, ['record' => $this->registration->id])
            ->callAction(SettlePaymentAction::class)
            ->assertNotified();

        $this->assertSame(RegistrationPayment::STATUS_PAID, $this->payment->refresh()->status);
        $this->assertSame($this->verifier->id, $this->payment->verified_by);
    }

    // ── Yang tidak boleh ─────────────────────────────────────────────

    public function test_verifier_cannot_open_the_registration_edit_page(): void
    {
        $this->get(SpmbRegistrationResource::getUrl('edit', ['record' => $this->registration]))
            ->assertForbidden();
    }

    public function test_verifier_cannot_open_the_payment_edit_page(): void
    {
        $this->get(RegistrationPaymentResource::getUrl('edit', ['record' => $this->payment]))
            ->assertForbidden();
    }

    public function test_verifier_is_not_offered_the_edit_or_delete_actions(): void
    {
        Livewire::test(ViewSpmbRegistration::class, ['record' => $this->registration->id])
            ->assertActionHidden('edit');

        Livewire::test(ViewRegistrationPayment::class, ['record' => $this->payment->id])
            ->assertActionHidden('edit');
    }

    public function test_verifier_cannot_delete_registrations(): void
    {
        $this->assertFalse($this->verifier->can('delete', $this->registration));
        $this->assertFalse($this->verifier->can('deleteAny', SpmbRegistration::class));
        $this->assertFalse($this->verifier->can('create', SpmbRegistration::class));
    }

    /**
     * A plain panel user holds neither custom permission, so the status and
     * verification actions must stay out of reach even though the record is
     * visible to them.
     */
    public function test_a_reader_without_the_custom_permissions_cannot_act(): void
    {
        $reader = $this->panelUser('SpmbRegistration', 'RegistrationPayment');
        $reader->revokePermissionTo(['UpdateStatus:SpmbRegistration', 'Verify:RegistrationPayment']);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->actingAs($reader);

        Livewire::test(ListSpmbRegistrations::class)
            ->assertActionHidden(TestAction::make('ubahStatus')->table($this->registration));

        Livewire::test(ListRegistrationPayments::class)
            ->assertActionHidden(TestAction::make('verifikasi')->table($this->payment));

        $this->assertSame(RegistrationPayment::STATUS_WAITING, $this->payment->refresh()->status);
    }
}
