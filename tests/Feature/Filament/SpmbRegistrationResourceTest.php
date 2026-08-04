<?php

namespace Tests\Feature\Filament;

use App\Filament\Actions\UpdateRegistrationStatusAction;
use App\Filament\Resources\SpmbRegistrations\Pages\EditSpmbRegistration;
use App\Filament\Resources\SpmbRegistrations\Pages\ListSpmbRegistrations;
use App\Filament\Resources\SpmbRegistrations\Pages\ViewSpmbRegistration;
use App\Models\Institution;
use App\Models\RegistrationPayment;
use App\Models\SpmbRegistration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SpmbRegistrationResourceTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = $this->panelUser('SpmbRegistration');

        $this->actingAs($this->user);
    }

    /**
     * Regression: a 16-digit NIK must round-trip as an exact string. Casting
     * it to a float turned it into scientific notation (e.g. 2.97E+15), which
     * both corrupted the value and overflowed the VARCHAR(16) column.
     */
    public function test_editing_preserves_full_sixteen_digit_nik(): void
    {
        $registration = SpmbRegistration::factory()->pending()->create([
            'nik' => '2975378084746545',
        ]);

        Livewire::test(EditSpmbRegistration::class, ['record' => $registration->id])
            ->fillForm(['status' => 'verified'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas(SpmbRegistration::class, [
            'id' => $registration->id,
            'nik' => '2975378084746545',
            'status' => 'verified',
        ]);
    }

    public function test_editing_rejects_nik_that_is_not_sixteen_digits(): void
    {
        $registration = SpmbRegistration::factory()->create();

        Livewire::test(EditSpmbRegistration::class, ['record' => $registration->id])
            ->fillForm(['nik' => '12345'])
            ->call('save')
            ->assertHasFormErrors(['nik']);
    }

    public function test_the_preview_page_shows_the_registration_read_only(): void
    {
        $registration = SpmbRegistration::factory()->pending()->create([
            'full_name' => 'Budi Santoso',
            'nik' => '2975378084746545',
        ]);

        Livewire::test(ViewSpmbRegistration::class, ['record' => $registration->id])
            ->assertOk()
            ->assertSchemaStateSet([
                'full_name' => 'Budi Santoso',
                'nik' => '2975378084746545',
                'status' => 'pending',
            ]);
    }

    /**
     * A jenjang whose formulir collects neither nomor HP nor sekolah asal — a
     * TK, typically — stores both as null, and the panel must still render.
     */
    public function test_the_list_and_preview_handle_a_pendaftar_without_phone_or_previous_school(): void
    {
        $registration = SpmbRegistration::factory()->pending()->create([
            'full_name' => 'Anak TK',
            'phone' => null,
            'previous_school' => null,
            'previous_school_city' => null,
        ]);

        Livewire::test(ListSpmbRegistrations::class)
            ->assertOk()
            ->assertCanSeeTableRecords([$registration]);

        Livewire::test(ViewSpmbRegistration::class, ['record' => $registration->id])
            ->assertOk()
            ->assertSee('Anak TK');
    }

    /**
     * The payment lives on the preview so panitia never has to leave the
     * pendaftar to see whether the tagihan is settled.
     */
    public function test_the_preview_page_shows_the_payment_inline(): void
    {
        $institution = Institution::factory()->create(['registration_fee' => 150_000]);
        $registration = SpmbRegistration::factory()->pending()->create([
            'institution_id' => $institution->id,
        ]);
        $payment = RegistrationPayment::factory()->waitingVerification()->create([
            'spmb_registration_id' => $registration->id,
            'amount' => 150_000,
        ]);

        Livewire::test(ViewSpmbRegistration::class, ['record' => $registration->id])
            ->assertOk()
            ->assertSee($payment->invoice_number)
            ->assertSee(rupiah($payment->total()))
            ->assertSee($payment->sender_name);
    }

    public function test_changing_status_from_the_preview_leaves_the_pendaftar_data_untouched(): void
    {
        $registration = SpmbRegistration::factory()->pending()->create([
            'full_name' => 'Budi Santoso',
            'nik' => '2975378084746545',
        ]);

        Livewire::test(ViewSpmbRegistration::class, ['record' => $registration->id])
            ->callAction(UpdateRegistrationStatusAction::class, ['status' => 'rejected'])
            ->assertNotified();

        $this->assertDatabaseHas(SpmbRegistration::class, [
            'id' => $registration->id,
            'status' => 'rejected',
            'full_name' => 'Budi Santoso',
            'nik' => '2975378084746545',
        ]);
    }
}
