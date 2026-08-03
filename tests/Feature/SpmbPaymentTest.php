<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\AdmissionPath;
use App\Models\Institution;
use App\Models\RegistrationPayment;
use App\Models\RegistrationWave;
use App\Models\Setting;
use App\Models\SpmbRegistration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Testing\TestResponse;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class SpmbPaymentTest extends TestCase
{
    use RefreshDatabase;

    private Institution $institution;

    private AcademicYear $year;

    private RegistrationWave $openWave;

    private AdmissionPath $path;

    protected function setUp(): void
    {
        parent::setUp();

        $this->institution = Institution::factory()->create([
            'slug' => 'smp',
            'short_name' => 'SMP',
            'registration_fee' => 150_000,
        ]);
        $this->year = AcademicYear::factory()->active()->create();
        $this->openWave = RegistrationWave::factory()->open()->create([
            'academic_year_id' => $this->year->id,
            'institution_id' => $this->institution->id,
        ]);
        $this->path = AdmissionPath::firstOrCreate(
            ['slug' => 'zonasi'],
            ['name' => 'Zonasi', 'is_active' => true],
        );

        Setting::setMany([
            'spmb_form_enabled' => '1',
            'spmb_payment_enabled' => '1',
            'spmb_payment_unique_code' => '1',
            'spmb_payment_deadline_hours' => '48',
            'spmb_bank_accounts' => json_encode([
                ['bank' => 'BSI', 'number' => '7123456789', 'holder' => 'Yayasan Nurul Islam'],
            ]),
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function submitRegistration(array $overrides = []): TestResponse
    {
        return $this->post(route('ppdb.store', $this->institution), array_merge([
            'full_name' => 'Budi Santoso',
            'nik' => '3273010101080001',
            'phone' => '081234567890',
            'previous_school' => 'SMP Negeri 1',
            'admission_path_id' => $this->path->id,
        ], $overrides));
    }

    // ── Penerbitan tagihan ───────────────────────────────────────────

    public function test_submitting_a_paid_jenjang_issues_a_tagihan_and_redirects_to_the_status_page(): void
    {
        $response = $this->submitRegistration();

        $registration = SpmbRegistration::firstWhere('nik', '3273010101080001');
        $this->assertNotNull($registration);

        $payment = $registration->payment;
        $this->assertNotNull($payment);
        $this->assertSame(150_000, $payment->amount);
        $this->assertSame(RegistrationPayment::STATUS_UNPAID, $payment->status);
        $this->assertGreaterThan(0, $payment->unique_code);
        $this->assertSame(150_000 + $payment->unique_code, $payment->total());
        $this->assertNotNull($payment->expires_at);
        $this->assertStringStartsWith("INV-SMP-{$this->year->year_start}-", $payment->invoice_number);

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertStringContainsString('/ppdb/status/'.$registration->id, $response->headers->get('Location'));
    }

    public function test_no_tagihan_is_issued_when_payment_handling_is_disabled(): void
    {
        Setting::set('spmb_payment_enabled', '0');

        $response = $this->submitRegistration();

        $response->assertRedirect(route('ppdb.show', $this->institution));
        $this->assertDatabaseCount('registration_payments', 0);
    }

    public function test_no_tagihan_is_issued_when_the_jenjang_charges_nothing(): void
    {
        $this->institution->update(['registration_fee' => 0]);

        $response = $this->submitRegistration();

        $response->assertRedirect(route('ppdb.show', $this->institution));
        $this->assertDatabaseCount('registration_payments', 0);
    }

    public function test_unique_code_is_zero_when_the_feature_is_switched_off(): void
    {
        Setting::set('spmb_payment_unique_code', '0');

        $this->submitRegistration();

        $this->assertSame(0, SpmbRegistration::firstWhere('nik', '3273010101080001')->payment->unique_code);
    }

    public function test_unique_codes_do_not_collide_between_outstanding_tagihan(): void
    {
        $codes = collect(range(1, 5))->map(function (int $index): int {
            $registration = SpmbRegistration::factory()->create([
                'institution_id' => $this->institution->id,
                'academic_year_id' => $this->year->id,
                'registration_wave_id' => $this->openWave->id,
            ]);

            return RegistrationPayment::issueFor($registration)->unique_code;
        });

        $this->assertCount(5, $codes->unique());
    }

    public function test_a_tagihan_is_never_issued_twice_for_one_registration(): void
    {
        $registration = SpmbRegistration::factory()->create([
            'institution_id' => $this->institution->id,
            'academic_year_id' => $this->year->id,
        ]);

        $first = RegistrationPayment::issueFor($registration);

        $this->assertNotNull($first);
        $this->assertNull(RegistrationPayment::issueFor($registration->refresh()));
        $this->assertSame(1, RegistrationPayment::where('spmb_registration_id', $registration->id)->count());
    }

    public function test_a_tagihan_can_be_issued_for_a_registration_submitted_before_the_fee_was_set(): void
    {
        // Registered while the jenjang still had no nominal, so no tagihan.
        $this->institution->update(['registration_fee' => null]);

        $this->submitRegistration();
        $registration = SpmbRegistration::firstWhere('nik', '3273010101080001');
        $this->assertNull($registration->payment);

        // Panitia fills in the nominal, then issues the tagihan from the panel.
        $this->institution->update(['registration_fee' => 150_000]);

        $payment = RegistrationPayment::issueFor($registration);

        $this->assertNotNull($payment);
        $this->assertSame(150_000, $payment->amount);
        $this->get(URL::signedRoute('ppdb.payment', $registration))
            ->assertOk()
            ->assertSee('name="proof"', false);
    }

    // ── Halaman status ───────────────────────────────────────────────

    public function test_status_page_rejects_an_unsigned_url(): void
    {
        $registration = SpmbRegistration::factory()->create(['institution_id' => $this->institution->id]);

        $this->get(route('ppdb.payment', $registration))->assertForbidden();
    }

    public function test_status_page_shows_the_tagihan_and_rekening_tujuan(): void
    {
        $registration = SpmbRegistration::factory()->create(['institution_id' => $this->institution->id]);
        $payment = RegistrationPayment::issueFor($registration);

        $response = $this->get(URL::signedRoute('ppdb.payment', $registration));

        $response->assertOk();
        $response->assertSee($payment->invoice_number);
        $response->assertSee(rupiah($payment->total()));
        $response->assertSee('7123456789');
        $response->assertSee('name="proof"', false);

        // The dropzone component, not a bare file input.
        $response->assertSee('Klik untuk memilih berkas');
    }

    public function test_status_page_offers_a_copy_link_button(): void
    {
        $registration = SpmbRegistration::factory()->create(['institution_id' => $this->institution->id]);
        RegistrationPayment::issueFor($registration);

        $response = $this->get(URL::signedRoute('ppdb.payment', $registration));

        $response->assertOk();
        $response->assertSee('Salin Tautan');
        $response->assertSee('Simpan Tautan Halaman Ini');
        // The field is prefilled with the canonical signed URL, signature included.
        $response->assertSee('signature');
    }

    public function test_status_page_without_a_tagihan_still_shows_the_registration(): void
    {
        Setting::set('spmb_payment_enabled', '0');

        $registration = SpmbRegistration::factory()->create([
            'institution_id' => $this->institution->id,
            'full_name' => 'Tanpa Tagihan',
        ]);

        $response = $this->get(URL::signedRoute('ppdb.payment', $registration));

        $response->assertOk();
        $response->assertSee('Tanpa Tagihan');
        $response->assertDontSee('name="proof"', false);
    }

    // ── Lookup ───────────────────────────────────────────────────────

    public function test_lookup_resolves_a_registration_by_nomor_pendaftaran_and_phone(): void
    {
        $registration = SpmbRegistration::factory()->create([
            'institution_id' => $this->institution->id,
            'phone' => '081298765432',
        ]);

        $response = $this->post(route('ppdb.status.find'), [
            'identity' => $registration->registration_number,
            'phone' => '081298765432',
        ]);

        $response->assertRedirect();
        $this->assertStringContainsString('/ppdb/status/'.$registration->id, $response->headers->get('Location'));

        // The signed URL handed back is immediately usable.
        $this->get($response->headers->get('Location'))->assertOk();
    }

    public function test_lookup_works_with_the_nik_when_the_nomor_pendaftaran_is_forgotten(): void
    {
        $registration = SpmbRegistration::factory()->create([
            'institution_id' => $this->institution->id,
            'nik' => '3273010101080002',
            'phone' => '081298765432',
        ]);

        $response = $this->post(route('ppdb.status.find'), [
            'identity' => '3273010101080002',
            'phone' => '081298765432',
        ]);

        $response->assertRedirect();
        $this->assertStringContainsString('/ppdb/status/'.$registration->id, $response->headers->get('Location'));
    }

    public function test_lookup_accepts_the_parent_phone_and_ignores_formatting(): void
    {
        $registration = SpmbRegistration::factory()->create([
            'institution_id' => $this->institution->id,
            'nik' => '3273010101080004',
            'phone' => '081200000000',
            'parent_phone' => '0813-9876-5432',
        ]);

        $response = $this->post(route('ppdb.status.find'), [
            'identity' => '3273010101080004',
            'phone' => '081398765432',
        ]);

        $response->assertRedirect();
        $this->assertStringContainsString('/ppdb/status/'.$registration->id, $response->headers->get('Location'));
    }

    public function test_lookup_fails_when_the_phone_does_not_match_the_identity(): void
    {
        $registration = SpmbRegistration::factory()->create([
            'institution_id' => $this->institution->id,
            'nik' => '3273010101080003',
            'phone' => '081211112222',
            'parent_phone' => null,
        ]);

        $response = $this->post(route('ppdb.status.find'), [
            'identity' => $registration->registration_number,
            'phone' => '089999999999',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertStringNotContainsString('/ppdb/status/'.$registration->id, $response->headers->get('Location'));
    }

    public function test_lookup_does_not_leak_another_pendaftar_sharing_a_phone_number(): void
    {
        $sibling = SpmbRegistration::factory()->create([
            'institution_id' => $this->institution->id,
            'nik' => '3273010101080005',
            'parent_phone' => '081355556666',
        ]);

        SpmbRegistration::factory()->create([
            'institution_id' => $this->institution->id,
            'nik' => '3273010101080006',
            'parent_phone' => '081355556666',
        ]);

        // The NIK still pins the lookup to exactly one of the two.
        $response = $this->post(route('ppdb.status.find'), [
            'identity' => '3273010101080005',
            'phone' => '081355556666',
        ]);

        $this->assertStringContainsString('/ppdb/status/'.$sibling->id, $response->headers->get('Location'));
    }

    // ── Unggah bukti ─────────────────────────────────────────────────

    public function test_uploading_a_bukti_queues_the_tagihan_for_verification(): void
    {
        Storage::fake('local');

        $registration = SpmbRegistration::factory()->create(['institution_id' => $this->institution->id]);
        $payment = RegistrationPayment::issueFor($registration);

        $response = $this->post(URL::signedRoute('ppdb.payment.proof', $registration), [
            'sender_name' => 'Ayah Budi',
            'bank_account' => 0,
            'transferred_on' => now()->toDateString(),
            'proof' => UploadedFile::fake()->create('bukti.jpg', 100, 'image/jpeg'),
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $payment->refresh();
        $this->assertSame(RegistrationPayment::STATUS_WAITING, $payment->status);
        $this->assertSame('Ayah Budi', $payment->sender_name);
        $this->assertSame('BSI 7123456789 a.n. Yayasan Nurul Islam', $payment->bank_account);
        $this->assertNotNull($payment->submitted_at);
        Storage::disk('local')->assertExists($payment->proof_path);
    }

    public function test_uploading_a_bukti_validates_its_input(): void
    {
        Storage::fake('local');

        $registration = SpmbRegistration::factory()->create(['institution_id' => $this->institution->id]);
        RegistrationPayment::issueFor($registration);

        $this->post(URL::signedRoute('ppdb.payment.proof', $registration), [
            'transferred_on' => now()->addWeek()->toDateString(),
        ])->assertSessionHasErrors(['sender_name', 'bank_account', 'transferred_on', 'proof']);
    }

    public function test_a_settled_tagihan_no_longer_accepts_a_bukti(): void
    {
        Storage::fake('local');

        $registration = SpmbRegistration::factory()->create(['institution_id' => $this->institution->id]);
        $payment = RegistrationPayment::issueFor($registration);
        $payment->markPaid();

        $response = $this->post(URL::signedRoute('ppdb.payment.proof', $registration), [
            'sender_name' => 'Ayah Budi',
            'bank_account' => 0,
            'transferred_on' => now()->toDateString(),
            'proof' => UploadedFile::fake()->create('bukti.jpg', 100, 'image/jpeg'),
        ]);

        $response->assertSessionHas('error');
        $this->assertSame(RegistrationPayment::STATUS_PAID, $payment->refresh()->status);
    }

    public function test_an_expired_tagihan_no_longer_accepts_a_bukti(): void
    {
        $registration = SpmbRegistration::factory()->create(['institution_id' => $this->institution->id]);
        $payment = RegistrationPayment::issueFor($registration);
        $payment->update(['expires_at' => now()->subHour()]);

        $this->assertTrue($payment->isExpired());
        $this->assertFalse($payment->acceptsProof());

        $this->get(URL::signedRoute('ppdb.payment', $registration))
            ->assertOk()
            ->assertDontSee('name="proof"', false);
    }

    public function test_a_rejected_bukti_can_be_uploaded_again(): void
    {
        Storage::fake('local');

        $registration = SpmbRegistration::factory()->create(['institution_id' => $this->institution->id]);
        $payment = RegistrationPayment::issueFor($registration);
        $payment->markRejected(null, 'Nominal tidak sesuai.');

        $this->assertTrue($payment->refresh()->acceptsProof());

        $response = $this->get(URL::signedRoute('ppdb.payment', $registration));
        $response->assertOk();
        $response->assertSee('Nominal tidak sesuai.');
        $response->assertSee('name="proof"', false);

        $this->post(URL::signedRoute('ppdb.payment.proof', $registration), [
            'sender_name' => 'Ayah Budi',
            'bank_account' => 0,
            'transferred_on' => now()->toDateString(),
            'proof' => UploadedFile::fake()->create('bukti-baru.jpg', 100, 'image/jpeg'),
        ])->assertSessionHas('success');

        $payment->refresh();
        $this->assertSame(RegistrationPayment::STATUS_WAITING, $payment->status);
        $this->assertNull($payment->note);
    }

    public function test_a_resubmitted_bukti_replaces_the_previous_file(): void
    {
        Storage::fake('local');

        $registration = SpmbRegistration::factory()->create(['institution_id' => $this->institution->id]);
        $payment = RegistrationPayment::issueFor($registration);

        $this->post(URL::signedRoute('ppdb.payment.proof', $registration), [
            'sender_name' => 'Ayah Budi',
            'bank_account' => 0,
            'transferred_on' => now()->toDateString(),
            'proof' => UploadedFile::fake()->create('bukti-lama.jpg', 100, 'image/jpeg'),
        ]);

        $firstPath = $payment->refresh()->proof_path;
        $payment->markRejected(null, 'Bukti buram.');

        $this->post(URL::signedRoute('ppdb.payment.proof', $registration), [
            'sender_name' => 'Ayah Budi',
            'bank_account' => 0,
            'transferred_on' => now()->toDateString(),
            'proof' => UploadedFile::fake()->create('bukti-baru.jpg', 100, 'image/jpeg'),
        ]);

        $payment->refresh();
        $this->assertNotSame($firstPath, $payment->proof_path);
        Storage::disk('local')->assertMissing($firstPath);
        Storage::disk('local')->assertExists($payment->proof_path);
    }

    // ── Verifikasi panitia ───────────────────────────────────────────

    public function test_marking_a_tagihan_paid_promotes_a_pending_registration(): void
    {
        $registration = SpmbRegistration::factory()->pending()->create(['institution_id' => $this->institution->id]);
        $payment = RegistrationPayment::issueFor($registration);

        $admin = User::factory()->create();
        $payment->markPaid($admin);

        $this->assertSame(RegistrationPayment::STATUS_PAID, $payment->status);
        $this->assertSame($admin->id, $payment->verified_by);
        $this->assertSame('verified', $registration->refresh()->status);
        $this->assertNotNull($registration->verified_at);
    }

    public function test_marking_a_tagihan_paid_leaves_a_decided_registration_alone(): void
    {
        $registration = SpmbRegistration::factory()->accepted()->create(['institution_id' => $this->institution->id]);
        $payment = RegistrationPayment::issueFor($registration);

        $payment->markPaid();

        $this->assertSame('accepted', $registration->refresh()->status);
    }

    public function test_bukti_download_is_guarded_and_streams_the_file(): void
    {
        Storage::fake('local');

        $path = "ppdb-bukti/{$this->institution->id}/bukti.jpg";
        Storage::disk('local')->put($path, 'dummy-content');

        $registration = SpmbRegistration::factory()->create(['institution_id' => $this->institution->id]);
        $payment = RegistrationPayment::factory()->waitingVerification()->create([
            'spmb_registration_id' => $registration->id,
            'proof_path' => $path,
        ]);

        // Guests are bounced to login.
        $this->get(route('ppdb.payment.download', $payment))->assertRedirect(route('login'));

        // Panel users without the permission are refused.
        $this->actingAs(User::factory()->create())
            ->get(route('ppdb.payment.download', $payment))
            ->assertForbidden();

        // Authorised panel users get the file streamed back.
        $admin = User::factory()->create();
        $admin->givePermissionTo(Permission::findOrCreate('View:RegistrationPayment', 'web'));
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->actingAs($admin)
            ->get(route('ppdb.payment.download', $payment))
            ->assertOk();
    }

    /**
     * The preview route renders a bukti in the browser instead of downloading
     * it, but must never let a user upload dictate how it is interpreted.
     */
    public function test_bukti_preview_is_served_inline_with_a_pinned_content_type(): void
    {
        Storage::fake('local');

        $path = "ppdb-bukti/{$this->institution->id}/bukti.jpg";
        Storage::disk('local')->put($path, 'dummy-content');

        $payment = RegistrationPayment::factory()->waitingVerification()->create([
            'spmb_registration_id' => SpmbRegistration::factory()->create(['institution_id' => $this->institution->id])->id,
            'proof_path' => $path,
        ]);

        $admin = User::factory()->create();
        $admin->givePermissionTo(Permission::findOrCreate('View:RegistrationPayment', 'web'));
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $response = $this->actingAs($admin)->get(route('ppdb.payment.preview', $payment));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'image/jpeg');
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->assertStringStartsWith('inline', $response->headers->get('Content-Disposition'));
    }

    public function test_bukti_preview_is_guarded_like_the_download(): void
    {
        Storage::fake('local');

        $path = "ppdb-bukti/{$this->institution->id}/bukti.jpg";
        Storage::disk('local')->put($path, 'dummy-content');

        $payment = RegistrationPayment::factory()->waitingVerification()->create([
            'spmb_registration_id' => SpmbRegistration::factory()->create(['institution_id' => $this->institution->id])->id,
            'proof_path' => $path,
        ]);

        $this->get(route('ppdb.payment.preview', $payment))->assertRedirect(route('login'));

        $this->actingAs(User::factory()->create())
            ->get(route('ppdb.payment.preview', $payment))
            ->assertForbidden();
    }

    public function test_bukti_preview_refuses_a_file_type_it_cannot_pin(): void
    {
        Storage::fake('local');

        $path = "ppdb-bukti/{$this->institution->id}/bukti.svg";
        Storage::disk('local')->put($path, '<svg xmlns="http://www.w3.org/2000/svg"></svg>');

        $payment = RegistrationPayment::factory()->waitingVerification()->create([
            'spmb_registration_id' => SpmbRegistration::factory()->create(['institution_id' => $this->institution->id])->id,
            'proof_path' => $path,
        ]);

        $admin = User::factory()->create();
        $admin->givePermissionTo(Permission::findOrCreate('View:RegistrationPayment', 'web'));
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->actingAs($admin)
            ->get(route('ppdb.payment.preview', $payment))
            ->assertNotFound();
    }

    public function test_the_ppdb_page_advertises_the_registration_fee(): void
    {
        $response = $this->get(route('ppdb.show', $this->institution));

        $response->assertOk();
        $response->assertSee(rupiah(150_000));
        $response->assertSee(route('ppdb.status'), false);
    }
}
