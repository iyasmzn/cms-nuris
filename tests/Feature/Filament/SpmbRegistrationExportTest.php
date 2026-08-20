<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\SpmbRegistrations\Pages\ListSpmbRegistrations;
use App\Models\Institution;
use App\Models\PpdbField;
use App\Models\SpmbRegistration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use OpenSpout\Reader\XLSX\Reader;
use Tests\TestCase;

class SpmbRegistrationExportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs($this->panelUser('SpmbRegistration'));
    }

    /**
     * Narrowing the list to one jenjang settles which dynamic fields the
     * pendaftar were asked for, so the sheet can carry a column per field.
     */
    public function test_exporting_a_single_jenjang_appends_its_dynamic_fields(): void
    {
        $institution = Institution::factory()->create(['short_name' => 'SMP']);

        PpdbField::factory()->select(['Laki-laki', 'Perempuan'])->create([
            'institution_id' => $institution->id,
            'key' => 'jenis_kelamin',
            'label' => 'Jenis Kelamin',
            'sort_order' => 1,
        ]);
        PpdbField::factory()->create([
            'institution_id' => $institution->id,
            'key' => 'hobi',
            'label' => 'Hobi',
            'sort_order' => 2,
        ]);

        SpmbRegistration::factory()->create([
            'institution_id' => $institution->id,
            'full_name' => 'Budi Santoso',
            'data' => ['jenis_kelamin' => 'Laki-laki', 'hobi' => 'Memanah'],
        ]);

        $rows = $this->export(['institution_id' => $institution->id]);

        $this->assertContains('Jenis Kelamin', $rows[0]);
        $this->assertContains('Hobi', $rows[0]);
        $this->assertContains('Laki-laki', $rows[1]);
        $this->assertContains('Memanah', $rows[1]);

        // Kolom baku tetap ikut terisi.
        $this->assertContains('Budi Santoso', $rows[1]);
        $this->assertContains('SMP', $rows[1]);
    }

    /**
     * Without a jenjang there is no single field set to widen the sheet with, so
     * the export keeps its fixed columns for every jenjang at once.
     */
    public function test_exporting_every_jenjang_keeps_the_fixed_columns(): void
    {
        $sd = Institution::factory()->create(['short_name' => 'SD']);
        $smp = Institution::factory()->create(['short_name' => 'SMP']);

        PpdbField::factory()->create([
            'institution_id' => $sd->id,
            'key' => 'hobi',
            'label' => 'Hobi',
        ]);

        SpmbRegistration::factory()->create(['institution_id' => $sd->id, 'data' => ['hobi' => 'Memanah']]);
        SpmbRegistration::factory()->create(['institution_id' => $smp->id]);

        $rows = $this->export();

        $this->assertNotContains('Hobi', $rows[0]);
        $this->assertCount(3, $rows, 'Semua jenjang harus ikut terekspor.');
        $this->assertContains('SD', $rows[1]);
        $this->assertContains('SMP', $rows[2]);
    }

    /**
     * A field whose key is a pendaftar column already has a heading of its own —
     * it must not be repeated at the end of the row.
     */
    public function test_a_column_backed_field_is_not_repeated_as_an_extra_column(): void
    {
        $institution = Institution::factory()->create();

        PpdbField::factory()->create([
            'institution_id' => $institution->id,
            'key' => 'previous_school',
            'label' => 'Asal Sekolah',
        ]);

        SpmbRegistration::factory()->create([
            'institution_id' => $institution->id,
            'previous_school' => 'SDN 1',
        ]);

        $rows = $this->export(['institution_id' => $institution->id]);

        // Nama Lengkap & No. HP milik field terkunci juga sudah punya kolom baku.
        $this->assertNotContains('Asal Sekolah', $rows[0]);
        $this->assertSame(array_values(array_unique($rows[0])), array_values($rows[0]));
    }

    /**
     * A stored berkas path is useless in a spreadsheet, so it travels as the
     * panitia-only download link instead.
     */
    public function test_an_uploaded_berkas_is_exported_as_its_download_link(): void
    {
        $institution = Institution::factory()->create();

        PpdbField::factory()->create([
            'institution_id' => $institution->id,
            'key' => 'akta',
            'label' => 'Akta Kelahiran',
            'type' => 'file',
        ]);

        $registration = SpmbRegistration::factory()->create([
            'institution_id' => $institution->id,
            'data' => ['akta' => 'ppdb-berkas/1/akta.pdf'],
        ]);

        $rows = $this->export(['institution_id' => $institution->id]);

        $this->assertContains('Akta Kelahiran', $rows[0]);
        $this->assertContains(route('ppdb.berkas', [$registration, 'akta']), $rows[1]);
    }

    /**
     * Switching a field off in the panel must not silently drop the answers the
     * pendaftar already gave it — but an empty retired field is just clutter.
     */
    public function test_a_deactivated_field_is_exported_only_while_it_still_holds_answers(): void
    {
        $institution = Institution::factory()->create();

        PpdbField::factory()->create([
            'institution_id' => $institution->id,
            'key' => 'hobi',
            'label' => 'Hobi',
            'is_active' => false,
        ]);
        PpdbField::factory()->create([
            'institution_id' => $institution->id,
            'key' => 'cita_cita',
            'label' => 'Cita-cita',
            'is_active' => false,
        ]);

        SpmbRegistration::factory()->create([
            'institution_id' => $institution->id,
            'data' => ['hobi' => 'Memanah'],
        ]);

        $rows = $this->export(['institution_id' => $institution->id]);

        $this->assertContains('Hobi', $rows[0]);
        $this->assertContains('Memanah', $rows[1]);
        $this->assertNotContains('Cita-cita', $rows[0]);
    }

    /**
     * Run the export action and read back the spreadsheet it streamed.
     *
     * @param  array<string, mixed>|null  $jenjangFilter
     * @return list<list<mixed>>
     */
    private function export(?array $jenjangFilter = null): array
    {
        $component = Livewire::test(ListSpmbRegistrations::class);

        if ($jenjangFilter !== null) {
            $component->filterTable('jenjang', $jenjangFilter);
        }

        return $this->readXlsx($component->callAction('export'));
    }

    /**
     * @return list<list<mixed>>
     */
    private function readXlsx(Testable $component): array
    {
        $path = tempnam(sys_get_temp_dir(), 'export').'.xlsx';
        file_put_contents($path, base64_decode($component->effects['download']['content']));

        $reader = new Reader;
        $reader->open($path);

        $rows = [];

        foreach ($reader->getSheetIterator() as $sheet) {
            foreach ($sheet->getRowIterator() as $row) {
                $rows[] = $row->toArray();
            }

            break;
        }

        $reader->close();
        unlink($path);

        return $rows;
    }
}
