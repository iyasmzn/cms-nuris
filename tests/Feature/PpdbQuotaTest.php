<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\AdmissionPath;
use App\Models\Institution;
use App\Models\RegistrationWave;
use App\Models\Setting;
use App\Models\SpmbRegistration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PpdbQuotaTest extends TestCase
{
    use RefreshDatabase;

    private Institution $institution;

    private AcademicYear $year;

    private RegistrationWave $wave;

    private AdmissionPath $path;

    protected function setUp(): void
    {
        parent::setUp();

        $this->institution = Institution::factory()->create([
            'slug' => 'smp',
            'quota' => 2,
            'close_when_full' => true,
        ]);
        $this->year = AcademicYear::factory()->active()->create();
        $this->wave = RegistrationWave::factory()->open()->create([
            'academic_year_id' => $this->year->id,
            'institution_id' => $this->institution->id,
        ]);
        $this->path = AdmissionPath::firstOrCreate(
            ['slug' => 'zonasi'],
            ['name' => 'Zonasi', 'is_active' => true],
        );
        Setting::set('spmb_form_enabled', '1');
    }

    public function test_every_status_except_rejected_takes_a_slot(): void
    {
        $this->register('pending');
        $this->register('verified');
        $this->register('accepted');
        $this->register('rejected');

        $this->assertSame(3, $this->institution->quotaUsed());
    }

    public function test_only_the_active_academic_year_counts_toward_the_quota(): void
    {
        $pastYear = AcademicYear::factory()->create(['is_active' => false]);
        $this->register('accepted', $pastYear);
        $this->register('accepted', $pastYear);

        $this->assertSame(0, $this->institution->quotaUsed());
        $this->assertFalse($this->institution->isQuotaFull());
        $this->assertTrue($this->institution->registrationOpen());
    }

    public function test_eager_loaded_quota_usage_matches_the_live_count(): void
    {
        $this->register('pending');
        $this->register('rejected');

        $loaded = Institution::query()->withQuotaUsage()->find($this->institution->id);

        $this->assertSame(1, $loaded->quotaUsed());
        $this->assertSame(1, $loaded->remainingQuota());
    }

    public function test_a_full_quota_closes_registration(): void
    {
        $this->register('pending');
        $this->register('accepted');

        $this->assertTrue($this->institution->isQuotaFull());
        $this->assertTrue($this->institution->closedByQuota());
        $this->assertFalse($this->institution->registrationOpen());
        $this->assertFalse(SpmbRegistration::isOpen($this->institution));
    }

    public function test_a_full_quota_refuses_new_submissions(): void
    {
        $this->register('pending');
        $this->register('pending');

        $this->from(route('ppdb.show', $this->institution))
            ->post(route('ppdb.store', $this->institution), $this->submission())
            ->assertRedirect(route('ppdb.show', $this->institution))
            ->assertSessionHas('error', Institution::DEFAULT_QUOTA_FULL_MESSAGE);

        $this->assertSame(2, SpmbRegistration::count());
    }

    public function test_rejecting_a_pendaftar_frees_their_slot(): void
    {
        $this->register('pending');
        $this->register('rejected');

        $this->post(route('ppdb.store', $this->institution), $this->submission())
            ->assertSessionHasNoErrors();

        $this->assertSame(3, SpmbRegistration::count());
        $this->assertTrue($this->institution->fresh()->isQuotaFull());
    }

    public function test_quota_is_only_informational_when_auto_close_is_off(): void
    {
        $this->institution->update(['close_when_full' => false]);
        $this->register('pending');
        $this->register('pending');

        $this->assertTrue($this->institution->isQuotaFull());
        $this->assertFalse($this->institution->closedByQuota());

        $this->post(route('ppdb.store', $this->institution), $this->submission())
            ->assertSessionHasNoErrors();

        $this->assertSame(3, SpmbRegistration::count());
    }

    public function test_a_jenjang_without_quota_never_closes_by_quota(): void
    {
        $this->institution->update(['quota' => null]);
        $this->register('pending');
        $this->register('pending');

        $this->assertFalse($this->institution->hasQuota());
        $this->assertNull($this->institution->remainingQuota());
        $this->assertFalse($this->institution->closedByQuota());
    }

    public function test_a_non_internal_jenjang_never_closes_by_quota(): void
    {
        $this->institution->update([
            'form_mode' => Institution::FORM_MODE_EXTERNAL_LINK,
            'external_url' => 'https://daftar.example.test',
        ]);
        $this->register('pending');
        $this->register('pending');

        $this->assertFalse($this->institution->closesWhenFull());
        $this->assertTrue($this->institution->registrationOpen());
    }

    public function test_jenjang_page_explains_a_full_quota(): void
    {
        $this->institution->update(['quota_full_message' => 'Maaf, kursi SMP sudah habis.']);
        $this->register('pending');
        $this->register('pending');

        $this->get(route('ppdb.show', $this->institution))
            ->assertOk()
            ->assertSee('Kuota Pendaftaran Penuh')
            ->assertSee('Maaf, kursi SMP sudah habis.');
    }

    public function test_quota_full_message_falls_back_to_the_global_setting(): void
    {
        Setting::set('spmb_quota_full_message', 'Kuota global penuh.');

        $this->assertSame('Kuota global penuh.', $this->institution->resolvedQuotaFullMessage());

        $this->institution->quota_full_message = 'Kuota SMP penuh.';

        $this->assertSame('Kuota SMP penuh.', $this->institution->resolvedQuotaFullMessage());
    }

    public function test_ppdb_index_marks_a_jenjang_with_a_full_quota(): void
    {
        Institution::factory()->create(['slug' => 'sma', 'name' => 'SMA Contoh']);
        $this->register('pending');
        $this->register('pending');

        $this->get(route('ppdb.index'))
            ->assertOk()
            ->assertSee('Kuota Penuh');
    }

    private function register(string $status, ?AcademicYear $year = null): SpmbRegistration
    {
        $year ??= $this->year;

        return SpmbRegistration::factory()->create([
            'institution_id' => $this->institution->id,
            'academic_year_id' => $year->id,
            'registration_wave_id' => $year->is($this->year) ? $this->wave->id : RegistrationWave::factory()->create([
                'academic_year_id' => $year->id,
                'institution_id' => $this->institution->id,
            ])->id,
            'admission_path_id' => $this->path->id,
            'status' => $status,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function submission(): array
    {
        return [
            'full_name' => 'Calon '.fake()->unique()->firstName(),
            'nik' => fake()->unique()->numerify('################'),
            'previous_school' => 'SD Negeri 1',
            'phone' => '08'.fake()->unique()->numerify('#########'),
            'admission_path_id' => $this->path->id,
        ];
    }
}
