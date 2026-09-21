<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\RegistrationPayments\Pages\ListRegistrationPayments;
use App\Filament\Resources\RegistrationPayments\Pages\ViewRegistrationPayment;
use App\Filament\Resources\RegistrationPayments\RegistrationPaymentResource;
use App\Filament\Resources\SpmbRegistrations\Pages\ListSpmbRegistrations;
use App\Filament\Resources\SpmbRegistrations\Pages\ViewSpmbRegistration;
use App\Filament\Resources\SpmbRegistrations\SpmbRegistrationResource;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Models\Institution;
use App\Models\RegistrationPayment;
use App\Models\SpmbRegistration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Data pendaftar milik satu unit: akun SD hanya melihat pendaftar SD, akun TK
 * hanya TK. Satu akun boleh memegang beberapa unit, dan pemegang permission
 * `ViewAll:*` (termasuk super admin) tetap melihat semuanya.
 */
class InstitutionScopedAccessTest extends TestCase
{
    use RefreshDatabase;

    private Institution $sd;

    private Institution $tk;

    private SpmbRegistration $pendaftarSd;

    private SpmbRegistration $pendaftarTk;

    protected function setUp(): void
    {
        parent::setUp();

        $this->sd = Institution::factory()->create(['slug' => 'sd', 'short_name' => 'SD', 'registration_fee' => 150_000]);
        $this->tk = Institution::factory()->create(['slug' => 'tk', 'short_name' => 'TK', 'registration_fee' => 150_000]);

        $this->pendaftarSd = SpmbRegistration::factory()->pending()->create([
            'institution_id' => $this->sd->id,
            'full_name' => 'Pendaftar Unit SD',
        ]);
        $this->pendaftarTk = SpmbRegistration::factory()->pending()->create([
            'institution_id' => $this->tk->id,
            'full_name' => 'Pendaftar Unit TK',
        ]);
    }

    /**
     * A panitia account: the PPDB permissions, but none of the `ViewAll:*`
     * ones, so it only ever sees the jenjang attached to it.
     */
    private function unitUser(Institution ...$institutions): User
    {
        $user = User::factory()->create();

        $user->givePermissionTo(collect([
            'ViewAny:SpmbRegistration', 'View:SpmbRegistration', 'Update:SpmbRegistration', 'UpdateStatus:SpmbRegistration',
            'ViewAny:RegistrationPayment', 'View:RegistrationPayment', 'Verify:RegistrationPayment',
        ])->map(fn (string $name): Permission => Permission::findOrCreate($name, 'web')));

        $user->institutions()->attach(collect($institutions)->pluck('id'));

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return $user;
    }

    private function paymentFor(SpmbRegistration $registration): RegistrationPayment
    {
        return RegistrationPayment::factory()->waitingVerification()->create([
            'spmb_registration_id' => $registration->id,
            'amount' => 150_000,
            'proof_path' => "ppdb-bukti/{$registration->institution_id}/bukti.jpg",
        ]);
    }

    // ── Daftar pendaftar ─────────────────────────────────────────────

    public function test_a_unit_account_only_sees_its_own_pendaftar(): void
    {
        $this->actingAs($this->unitUser($this->sd));

        Livewire::test(ListSpmbRegistrations::class)
            ->assertOk()
            ->assertCanSeeTableRecords([$this->pendaftarSd])
            ->assertCanNotSeeTableRecords([$this->pendaftarTk]);
    }

    public function test_an_account_holding_two_units_sees_both(): void
    {
        $this->actingAs($this->unitUser($this->sd, $this->tk));

        Livewire::test(ListSpmbRegistrations::class)
            ->assertOk()
            ->assertCanSeeTableRecords([$this->pendaftarSd, $this->pendaftarTk]);
    }

    public function test_an_account_without_any_unit_sees_nothing(): void
    {
        $this->actingAs($this->unitUser());

        Livewire::test(ListSpmbRegistrations::class)
            ->assertOk()
            ->assertCanNotSeeTableRecords([$this->pendaftarSd, $this->pendaftarTk]);
    }

    public function test_a_holder_of_view_all_still_sees_every_unit(): void
    {
        $this->actingAs($this->panelUser('SpmbRegistration'));

        Livewire::test(ListSpmbRegistrations::class)
            ->assertOk()
            ->assertCanSeeTableRecords([$this->pendaftarSd, $this->pendaftarTk]);
    }

    // ── Membuka record unit lain ─────────────────────────────────────

    /**
     * The record never reaches the page: the resource query hides it and the
     * policy refuses it, so the panel answers 403 rather than rendering it.
     */
    public function test_opening_a_pendaftar_of_another_unit_is_refused(): void
    {
        $this->actingAs($this->unitUser($this->sd));

        $this->get(SpmbRegistrationResource::getUrl('view', ['record' => $this->pendaftarTk]))
            ->assertForbidden();
    }

    public function test_opening_a_pendaftar_of_its_own_unit_is_allowed(): void
    {
        $this->actingAs($this->unitUser($this->sd));

        Livewire::test(ViewSpmbRegistration::class, ['record' => $this->pendaftarSd->id])
            ->assertOk();
    }

    // ── Pembayaran ───────────────────────────────────────────────────

    public function test_a_unit_account_only_sees_the_tagihan_of_its_own_unit(): void
    {
        $tagihanSd = $this->paymentFor($this->pendaftarSd);
        $tagihanTk = $this->paymentFor($this->pendaftarTk);

        $this->actingAs($this->unitUser($this->sd));

        Livewire::test(ListRegistrationPayments::class)
            ->assertOk()
            ->assertCanSeeTableRecords([$tagihanSd])
            ->assertCanNotSeeTableRecords([$tagihanTk]);

        Livewire::test(ViewRegistrationPayment::class, ['record' => $tagihanSd->id])->assertOk();

        $this->get(RegistrationPaymentResource::getUrl('view', ['record' => $tagihanTk]))
            ->assertForbidden();
    }

    // ── Berkas & bukti di luar panel ─────────────────────────────────

    public function test_berkas_of_another_unit_cannot_be_downloaded(): void
    {
        Storage::fake('local');

        $path = "ppdb-berkas/{$this->tk->id}/ijazah.pdf";
        Storage::disk('local')->put($path, 'dummy-content');

        $this->tk->ppdbFields()->create([
            'key' => 'ijazah', 'label' => 'Ijazah', 'type' => 'file', 'is_required' => false, 'sort_order' => 1,
        ]);
        $this->pendaftarTk->update(['data' => ['ijazah' => $path]]);

        $this->actingAs($this->unitUser($this->sd))
            ->get(route('ppdb.berkas', [$this->pendaftarTk, 'ijazah']))
            ->assertForbidden();

        $this->actingAs($this->unitUser($this->tk))
            ->get(route('ppdb.berkas', [$this->pendaftarTk, 'ijazah']))
            ->assertOk();
    }

    public function test_bukti_of_another_unit_cannot_be_downloaded(): void
    {
        Storage::fake('local');

        $tagihanTk = $this->paymentFor($this->pendaftarTk);
        Storage::disk('local')->put($tagihanTk->proof_path, 'dummy-content');

        $this->actingAs($this->unitUser($this->sd))
            ->get(route('ppdb.payment.download', $tagihanTk))
            ->assertForbidden();

        $this->actingAs($this->unitUser($this->tk))
            ->get(route('ppdb.payment.download', $tagihanTk))
            ->assertOk();
    }

    // ── Badge navigasi & dasbor ──────────────────────────────────────

    public function test_the_navigation_badge_counts_only_its_own_unit(): void
    {
        $this->actingAs($this->unitUser($this->sd));

        $this->assertSame('1', SpmbRegistrationResource::getNavigationBadge());
    }

    public function test_an_admin_assigns_units_from_the_user_form(): void
    {
        $this->actingAs($this->panelUser('User'));

        $panitia = User::factory()->create();

        Livewire::test(EditUser::class, ['record' => $panitia->id])
            ->fillForm(['institutions' => [$this->sd->id, $this->tk->id]])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertEqualsCanonicalizing(
            [$this->sd->id, $this->tk->id],
            $panitia->refresh()->institutions->pluck('id')->all(),
        );
    }

    public function test_dashboard_numbers_count_only_its_own_unit(): void
    {
        $user = $this->unitUser($this->sd);

        $this->assertSame(1, SpmbRegistration::query()->visibleTo($user)->count());
        $this->assertSame(2, SpmbRegistration::query()->visibleTo($this->panelUser('SpmbRegistration'))->count());
    }
}
