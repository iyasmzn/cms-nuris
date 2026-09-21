<?php

namespace Tests\Feature\Filament;

use App\Filament\Widgets\RegistrationsPerChoiceFieldChart;
use App\Filament\Widgets\RegistrationsPerPaymentStatusChart;
use App\Models\Institution;
use App\Models\PpdbField;
use App\Models\RegistrationPayment;
use App\Models\Setting;
use App\Models\SpmbRegistration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Dua tambahan dasbor: pendaftar per status pembayaran, dan pendaftar per
 * pilihan pada formulir (mis. Pilihan Kelas) yang isinya mengikuti field
 * masing-masing jenjang.
 */
class DashboardPpdbWidgetsTest extends TestCase
{
    use RefreshDatabase;

    private Institution $sd;

    private Institution $tk;

    protected function setUp(): void
    {
        parent::setUp();

        $this->sd = Institution::factory()->create(['slug' => 'sd', 'short_name' => 'SD', 'registration_fee' => 150_000]);
        $this->tk = Institution::factory()->create(['slug' => 'tk', 'short_name' => 'TK', 'registration_fee' => 150_000]);
    }

    /**
     * An account that sees every jenjang and may view both widgets.
     */
    private function dashboardUser(): User
    {
        $user = $this->panelUser('SpmbRegistration', 'RegistrationPayment');

        $user->givePermissionTo(collect([
            'View:RegistrationsPerPaymentStatusChart',
            'View:RegistrationsPerChoiceFieldChart',
        ])->map(fn (string $name): Permission => Permission::findOrCreate($name, 'web')));

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return $user;
    }

    /**
     * Read a widget's chart payload without going through Livewire — the data
     * is what these tests are about, not the canvas.
     *
     * @return array{datasets: array<int, array<string, mixed>>, labels: array<int, string>}
     */
    private function chartData(object $widget): array
    {
        return (fn (): array => $this->getData())->call($widget);
    }

    private function payment(SpmbRegistration $registration, string $status): RegistrationPayment
    {
        return RegistrationPayment::factory()->create([
            'spmb_registration_id' => $registration->id,
            'amount' => 150_000,
            'status' => $status,
        ]);
    }

    // ── Pendaftar per status pembayaran ──────────────────────────────

    public function test_the_payment_widget_stays_hidden_while_payments_are_switched_off(): void
    {
        Setting::set('spmb_payment_enabled', '0');
        $this->actingAs($this->dashboardUser());

        $this->assertFalse(RegistrationsPerPaymentStatusChart::canView());
    }

    public function test_the_payment_widget_appears_once_payments_are_switched_on(): void
    {
        Setting::set('spmb_payment_enabled', '1');
        $this->actingAs($this->dashboardUser());

        $this->assertTrue(RegistrationsPerPaymentStatusChart::canView());
    }

    public function test_the_payment_widget_splits_pendaftar_by_their_tagihan(): void
    {
        Setting::set('spmb_payment_enabled', '1');
        $this->actingAs($this->dashboardUser());

        $lunas = SpmbRegistration::factory()->create(['institution_id' => $this->sd->id]);
        $menunggu = SpmbRegistration::factory()->create(['institution_id' => $this->sd->id]);
        $belum = SpmbRegistration::factory()->create(['institution_id' => $this->sd->id]);
        SpmbRegistration::factory()->create(['institution_id' => $this->sd->id]);

        $this->payment($lunas, RegistrationPayment::STATUS_PAID);
        $this->payment($menunggu, RegistrationPayment::STATUS_WAITING);
        $this->payment($belum, RegistrationPayment::STATUS_UNPAID);

        $data = $this->chartData(new RegistrationsPerPaymentStatusChart);

        $this->assertSame(
            ['Sudah Membayar' => 1, 'Butuh Konfirmasi' => 1, 'Belum Membayar' => 1, 'Tanpa Tagihan' => 1],
            array_combine($data['labels'], $data['datasets'][0]['data']),
        );
    }

    public function test_the_payment_widget_counts_only_the_units_the_account_handles(): void
    {
        Setting::set('spmb_payment_enabled', '1');

        $this->payment(SpmbRegistration::factory()->create(['institution_id' => $this->sd->id]), RegistrationPayment::STATUS_PAID);
        $this->payment(SpmbRegistration::factory()->create(['institution_id' => $this->tk->id]), RegistrationPayment::STATUS_PAID);

        $panitiaSd = User::factory()->create();
        $panitiaSd->givePermissionTo(Permission::findOrCreate('ViewAny:SpmbRegistration', 'web'));
        $panitiaSd->institutions()->attach($this->sd);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->actingAs($panitiaSd);

        $data = $this->chartData(new RegistrationsPerPaymentStatusChart);

        $this->assertSame(['Sudah Membayar'], $data['labels']);
        $this->assertSame([1], $data['datasets'][0]['data']);
    }

    // ── Pendaftar per pilihan (mis. Pilihan Kelas) ───────────────────

    private function pilihanKelas(Institution $institution, array $options): PpdbField
    {
        return PpdbField::factory()->select($options)->create([
            'institution_id' => $institution->id,
            'key' => 'pilihan_kelas',
            'label' => 'Pilihan Kelas',
            'show_in_dashboard' => true,
            'sort_order' => 1,
        ]);
    }

    public function test_the_choice_widget_stays_hidden_until_a_field_is_flagged(): void
    {
        $this->actingAs($this->dashboardUser());

        $this->assertFalse(RegistrationsPerChoiceFieldChart::canView());

        $this->pilihanKelas($this->sd, ['Kelas Reguler', 'Kelas Tahfizh']);

        $this->assertTrue(RegistrationsPerChoiceFieldChart::canView());
    }

    public function test_an_unflagged_choice_field_is_left_out(): void
    {
        $this->actingAs($this->dashboardUser());

        PpdbField::factory()->select(['Laki-laki', 'Perempuan'])->create([
            'institution_id' => $this->sd->id,
            'key' => 'jenis_kelamin',
            'label' => 'Jenis Kelamin',
            'show_in_dashboard' => false,
        ]);

        $this->assertFalse(RegistrationsPerChoiceFieldChart::canView());
    }

    public function test_the_choice_widget_counts_answers_per_option(): void
    {
        $this->actingAs($this->dashboardUser());

        $field = $this->pilihanKelas($this->sd, ['Kelas Reguler', 'Kelas Tahfizh']);

        SpmbRegistration::factory()->count(2)->create([
            'institution_id' => $this->sd->id,
            'data' => ['pilihan_kelas' => 'Kelas Reguler'],
        ]);
        SpmbRegistration::factory()->create([
            'institution_id' => $this->sd->id,
            'data' => ['pilihan_kelas' => 'Kelas Tahfizh'],
        ]);

        $widget = new RegistrationsPerChoiceFieldChart;
        $widget->filter = (string) $field->id;

        $data = $this->chartData($widget);

        $this->assertSame(['Kelas Reguler', 'Kelas Tahfizh'], $data['labels']);
        $this->assertSame([2, 1], $data['datasets'][0]['data']);
    }

    public function test_an_answer_no_longer_in_the_option_list_is_still_counted(): void
    {
        $this->actingAs($this->dashboardUser());

        $field = $this->pilihanKelas($this->sd, ['Kelas Reguler']);

        SpmbRegistration::factory()->create([
            'institution_id' => $this->sd->id,
            'data' => ['pilihan_kelas' => 'Kelas Reguler'],
        ]);
        SpmbRegistration::factory()->create([
            'institution_id' => $this->sd->id,
            'data' => ['pilihan_kelas' => 'Kelas Lama'],
        ]);
        SpmbRegistration::factory()->create(['institution_id' => $this->sd->id, 'data' => []]);

        $widget = new RegistrationsPerChoiceFieldChart;
        $widget->filter = (string) $field->id;

        $data = $this->chartData($widget);

        $this->assertSame(
            ['Kelas Reguler' => 1, 'Kelas Lama' => 1, 'Belum diisi' => 1],
            array_combine($data['labels'], $data['datasets'][0]['data']),
        );
    }

    public function test_each_jenjang_brings_its_own_choice_field(): void
    {
        $this->actingAs($this->dashboardUser());

        $sdField = $this->pilihanKelas($this->sd, ['Kelas Reguler', 'Kelas Tahfizh']);
        $tkField = $this->pilihanKelas($this->tk, ['KB', 'TK']);

        $widget = new RegistrationsPerChoiceFieldChart;
        $filters = (fn (): ?array => $this->getFilters())->call($widget);

        $this->assertSame([
            (string) $sdField->id => 'SD — Pilihan Kelas',
            (string) $tkField->id => 'TK — Pilihan Kelas',
        ], $filters);
    }

    public function test_the_choice_widget_offers_only_the_units_the_account_handles(): void
    {
        $sdField = $this->pilihanKelas($this->sd, ['Kelas Reguler', 'Kelas Tahfizh']);
        $this->pilihanKelas($this->tk, ['KB', 'TK']);

        $panitiaSd = User::factory()->create();
        $panitiaSd->givePermissionTo(Permission::findOrCreate('ViewAny:SpmbRegistration', 'web'));
        $panitiaSd->institutions()->attach($this->sd);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->actingAs($panitiaSd);

        $widget = new RegistrationsPerChoiceFieldChart;
        $filters = (fn (): ?array => $this->getFilters())->call($widget);

        $this->assertSame([(string) $sdField->id => 'SD — Pilihan Kelas'], $filters);
    }
}
