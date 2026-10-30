<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\RegistrationPayments\Pages\ListRegistrationPayments;
use App\Filament\Resources\RegistrationPayments\Pages\ViewRegistrationPayment;
use App\Models\Institution;
use App\Models\RegistrationPayment;
use App\Models\Setting;
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

    // ── Bayar tunai di kantor ────────────────────────────────────────

    /**
     * A tagihan nobody has paid yet, carrying a kode unik like a real one.
     */
    private function openPayment(): RegistrationPayment
    {
        $registration = SpmbRegistration::factory()->pending()->create([
            'institution_id' => $this->institution->id,
            'parent_name' => 'Ibu Siti',
        ]);

        return RegistrationPayment::factory()->create([
            'spmb_registration_id' => $registration->id,
            'amount' => 150_000,
            'unique_code' => 237,
        ]);
    }

    public function test_receiving_cash_settles_the_tagihan_without_the_kode_unik(): void
    {
        Setting::set('spmb_payment_cash_enabled', '1');
        $payment = $this->openPayment();

        Livewire::test(ListRegistrationPayments::class)
            ->set('activeTab', 'belum_bayar')
            ->callAction(TestAction::make('terimaTunai')->table($payment), [
                'payer_name' => 'Ibu Siti',
                'paid_on' => now()->toDateString(),
                'note' => 'Kwitansi 0012',
            ])
            ->assertHasNoActionErrors()
            ->assertNotified();

        $payment->refresh();
        $this->assertSame(RegistrationPayment::STATUS_PAID, $payment->status);
        $this->assertSame(RegistrationPayment::METHOD_CASH, $payment->method);
        $this->assertSame(150_000, $payment->total());
        $this->assertSame('Ibu Siti', $payment->sender_name);
        $this->assertSame('Kwitansi 0012', $payment->note);
        $this->assertSame($this->user->id, $payment->verified_by);
        $this->assertSame('verified', $payment->registration->refresh()->status);
    }

    public function test_the_cash_form_starts_with_the_parent_and_today(): void
    {
        Setting::set('spmb_payment_cash_enabled', '1');
        $payment = $this->openPayment();

        Livewire::test(ListRegistrationPayments::class)
            ->set('activeTab', 'belum_bayar')
            ->mountAction(TestAction::make('terimaTunai')->table($payment))
            ->assertActionDataSet([
                'payer_name' => 'Ibu Siti',
                'paid_on' => now()->toDateString(),
            ])
            ->assertMountedActionModalSee(rupiah(150_000));
    }

    public function test_a_cash_payment_needs_a_payer_and_cannot_be_dated_ahead(): void
    {
        Setting::set('spmb_payment_cash_enabled', '1');
        $payment = $this->openPayment();

        Livewire::test(ListRegistrationPayments::class)
            ->set('activeTab', 'belum_bayar')
            ->callAction(TestAction::make('terimaTunai')->table($payment), [
                'payer_name' => null,
                'paid_on' => now()->addDay()->toDateString(),
            ])
            ->assertHasActionErrors(['payer_name' => 'required', 'paid_on']);

        $this->assertSame(RegistrationPayment::STATUS_UNPAID, $payment->refresh()->status);
    }

    public function test_receiving_cash_is_not_offered_when_the_jenjang_takes_no_cash(): void
    {
        $payment = $this->openPayment();

        Livewire::test(ListRegistrationPayments::class)
            ->set('activeTab', 'belum_bayar')
            ->assertActionHidden(TestAction::make('terimaTunai')->table($payment));
    }

    public function test_receiving_cash_waits_until_a_pending_bukti_is_decided(): void
    {
        Setting::set('spmb_payment_cash_enabled', '1');
        $payment = $this->payment();

        Livewire::test(ListRegistrationPayments::class)
            ->assertActionHidden(TestAction::make('terimaTunai')->table($payment))
            ->assertActionVisible(TestAction::make('verifikasi')->table($payment));
    }

    public function test_payments_can_be_found_by_registration_number(): void
    {
        $match = $this->payment();
        $other = $this->payment();

        Livewire::test(ListRegistrationPayments::class)
            ->searchTable($match->registration->registration_number)
            ->assertCanSeeTableRecords([$match])
            ->assertCanNotSeeTableRecords([$other]);
    }

    public function test_payments_can_be_filtered_by_method(): void
    {
        $transfer = $this->payment('paid');
        $cash = RegistrationPayment::factory()->paidInCash()->create([
            'spmb_registration_id' => SpmbRegistration::factory()->create(['institution_id' => $this->institution->id])->id,
        ]);

        Livewire::test(ListRegistrationPayments::class)
            ->set('activeTab', 'lunas')
            ->filterTable('method', RegistrationPayment::METHOD_CASH)
            ->assertCanSeeTableRecords([$cash])
            ->assertCanNotSeeTableRecords([$transfer]);
    }

    public function test_the_preview_page_shows_a_cash_payment_instead_of_a_bukti(): void
    {
        $payment = RegistrationPayment::factory()->paidInCash()->create([
            'spmb_registration_id' => SpmbRegistration::factory()->create(['institution_id' => $this->institution->id])->id,
            'sender_name' => 'Ibu Siti',
        ]);

        Livewire::test(ViewRegistrationPayment::class, ['record' => $payment->id])
            ->assertOk()
            ->assertSee('Pembayaran Tunai')
            ->assertSee('Ibu Siti')
            ->assertSee('Tunai di kantor')
            ->assertDontSee('Bukti Transfer');
    }
}
