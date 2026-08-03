<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\RegistrationPayments\Pages\ListRegistrationPayments;
use App\Filament\Resources\RegistrationPayments\Pages\ViewRegistrationPayment;
use App\Models\Institution;
use App\Models\RegistrationPayment;
use App\Models\SpmbRegistration;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class RegistrationPaymentResourceTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Institution $institution;

    protected function setUp(): void
    {
        parent::setUp();

        $this->institution = Institution::factory()->create(['registration_fee' => 150_000]);

        $this->user = $this->panelUser('RegistrationPayment');

        $this->actingAs($this->user);
    }

    private function payment(string $state = 'waitingVerification'): RegistrationPayment
    {
        $registration = SpmbRegistration::factory()->pending()->create([
            'institution_id' => $this->institution->id,
        ]);

        return RegistrationPayment::factory()->{$state}()->create([
            'spmb_registration_id' => $registration->id,
            'amount' => 150_000,
        ]);
    }

    public function test_the_verification_queue_lists_only_bukti_awaiting_a_decision(): void
    {
        $waiting = $this->payment();
        $settled = $this->payment('paid');

        Livewire::test(ListRegistrationPayments::class)
            ->assertCanSeeTableRecords([$waiting])
            ->assertCanNotSeeTableRecords([$settled]);
    }

    public function test_marking_a_payment_paid_also_verifies_the_registration(): void
    {
        $payment = $this->payment();

        Livewire::test(ListRegistrationPayments::class)
            ->callAction(TestAction::make('verifikasi')->table($payment))
            ->assertNotified();

        $payment->refresh();
        $this->assertSame(RegistrationPayment::STATUS_PAID, $payment->status);
        $this->assertSame($this->user->id, $payment->verified_by);
        $this->assertSame('verified', $payment->registration->refresh()->status);
    }

    public function test_rejecting_a_bukti_records_the_reason_for_the_pendaftar(): void
    {
        $payment = $this->payment();

        Livewire::test(ListRegistrationPayments::class)
            ->callAction(TestAction::make('tolak')->table($payment), [
                'reason' => 'Nominal tidak sesuai kode unik.',
            ])
            ->assertNotified();

        $payment->refresh();
        $this->assertSame(RegistrationPayment::STATUS_REJECTED, $payment->status);
        $this->assertSame('Nominal tidak sesuai kode unik.', $payment->note);
        $this->assertTrue($payment->acceptsProof());
    }

    public function test_the_preview_page_shows_the_tagihan_read_only(): void
    {
        $payment = $this->payment();

        Livewire::test(ViewRegistrationPayment::class, ['record' => $payment->id])
            ->assertOk()
            ->assertSchemaStateSet([
                'invoice_number' => $payment->invoice_number,
                'status' => RegistrationPayment::STATUS_WAITING,
                'sender_name' => $payment->sender_name,
            ]);
    }

    public function test_the_preview_page_renders_the_bukti_inline_and_keeps_a_download_button(): void
    {
        $payment = $this->payment();

        Livewire::test(ViewRegistrationPayment::class, ['record' => $payment->id])
            ->assertOk()
            ->assertSee(route('ppdb.payment.preview', $payment), false)
            ->assertSee(route('ppdb.payment.download', $payment), false)
            ->assertSee('Unduh Berkas');
    }

    public function test_rejecting_a_bukti_requires_a_reason(): void
    {
        $payment = $this->payment();

        Livewire::test(ListRegistrationPayments::class)
            ->callAction(TestAction::make('tolak')->table($payment), ['reason' => null])
            ->assertHasActionErrors(['reason' => 'required']);

        $this->assertSame(RegistrationPayment::STATUS_WAITING, $payment->refresh()->status);
    }
}
