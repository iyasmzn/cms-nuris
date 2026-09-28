<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\AdmissionPath;
use App\Models\Faq;
use App\Models\Institution;
use App\Models\RegistrationWave;
use App\Models\Setting;
use App\Models\SpmbRegistration;
use App\Support\PpdbLanding;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PpdbLandingTest extends TestCase
{
    use RefreshDatabase;

    private Institution $smp;

    private Institution $sma;

    protected function setUp(): void
    {
        parent::setUp();

        $this->smp = Institution::factory()->create(['slug' => 'smp', 'name' => 'SMP Contoh', 'sort_order' => 1]);
        $this->sma = Institution::factory()->create(['slug' => 'sma', 'name' => 'SMA Contoh', 'sort_order' => 2]);
        Setting::set('spmb_form_enabled', '1');
    }

    public function test_default_page_shows_the_built_in_hero_and_every_jenjang(): void
    {
        $this->get(route('ppdb.index'))
            ->assertOk()
            ->assertSee('Penerimaan Peserta Didik Baru')
            ->assertSee('href="#jenjang"', false)
            ->assertSee('id="jenjang"', false)
            ->assertSeeInOrder(['SMP Contoh', 'SMA Contoh']);
    }

    public function test_a_single_jenjang_redirects_until_the_page_is_customized(): void
    {
        $this->sma->update(['is_active' => false]);

        $this->get(route('ppdb.index'))
            ->assertRedirect(route('ppdb.show', $this->smp));

        $this->saveSections([['type' => 'ppdb_institutions', 'heading' => 'Jenjang Kami']]);

        $this->get(route('ppdb.index'))
            ->assertOk()
            ->assertSee('Jenjang Kami');
    }

    public function test_saved_hero_replaces_the_default_and_fills_placeholders(): void
    {
        Setting::set('site_name', 'Sekolah Contoh');
        Setting::set(PpdbLanding::HERO_SETTING, json_encode([
            'badge' => 'Enrolment {year}',
            'title' => 'Selamat Datang di {site}',
            'highlight' => '',
            'subtitle' => 'Daftar mudah, banyak jalur beasiswa.',
            'primary_label' => 'Mulai Daftar',
            'primary_url' => '#jalur',
            'secondary_label' => '',
            'secondary_url' => '/ppdb/status',
        ]));

        $this->get(route('ppdb.index'))
            ->assertOk()
            ->assertSee('Selamat Datang di Sekolah Contoh')
            ->assertSee('Daftar mudah, banyak jalur beasiswa.')
            ->assertSee('Mulai Daftar')
            ->assertDontSee('Cek Status Pendaftaran')
            ->assertDontSee('{year}');
    }

    public function test_sections_render_in_the_saved_order_alongside_free_blocks(): void
    {
        AdmissionPath::factory()->create([
            'name' => 'Jalur Beasiswa Unggulan',
            'detail_url' => '/halaman/beasiswa',
            'is_active' => true,
        ]);

        $this->saveSections([
            ['type' => 'rich_text', 'heading' => 'Tentang Kami', 'content' => '<p>Sekolah berasrama terpadu.</p>'],
            ['type' => 'ppdb_paths', 'heading' => 'Jalur Penerimaan', 'intro' => 'Pilih jalur yang sesuai.', 'anchor' => 'jalur'],
        ]);

        $this->get(route('ppdb.index'))
            ->assertOk()
            ->assertSeeInOrder(['Tentang Kami', 'Sekolah berasrama terpadu.', 'Jalur Penerimaan', 'Pilih jalur yang sesuai.', 'Jalur Beasiswa Unggulan'])
            ->assertSee('href="/halaman/beasiswa"', false)
            ->assertSee('id="jalur"', false)
            ->assertSee('Semua jenjang');
    }

    public function test_a_path_card_with_a_detail_link_is_clickable_as_a_whole(): void
    {
        AdmissionPath::factory()->create([
            'name' => 'Jalur Tahfidz',
            'description' => 'Untuk penghafal Al-Qur\'an.',
            'detail_url' => 'https://contoh.sch.id/tahfidz',
        ]);
        AdmissionPath::factory()->create(['name' => 'Jalur Reguler', 'detail_url' => null]);

        $this->saveSections([['type' => 'ppdb_paths', 'heading' => 'Jalur Penerimaan']]);

        $response = $this->get(route('ppdb.index'))->assertOk();

        // Satu tautan membungkus seluruh kartu, dari gambar sampai "Lihat Selengkapnya".
        $response->assertSeeInOrder([
            'href="https://contoh.sch.id/tahfidz"',
            'target="_blank"',
            'pl-card-link',
            'Jalur Tahfidz',
            'Untuk penghafal Al-Qur&#039;an.',
            'Lihat Selengkapnya',
            '</a>',
        ], false);

        // Jalur tanpa tautan tetap kartu biasa: hanya satu kartu yang jadi tautan.
        $this->assertSame(1, substr_count($response->getContent(), 'class="pl-card-hover pl-card-link'));
        $this->assertSame(1, substr_count($response->getContent(), 'class="pl-card-cta'));
    }

    public function test_automatic_path_layout_uses_icon_cards_until_a_path_has_a_cover(): void
    {
        $path = AdmissionPath::factory()->create(['name' => 'Jalur Prestasi', 'icon' => '🏆', 'image' => null]);

        $this->saveSections([['type' => 'ppdb_paths', 'heading' => 'Jalur Penerimaan', 'paths_layout' => 'auto']]);

        $this->get(route('ppdb.index'))
            ->assertOk()
            ->assertSee('pl-paths-icon', false)
            ->assertSee('class="pl-path-icon pl-path-icon-lg"', false)
            ->assertDontSee('class="pl-path-media"', false);

        $path->update(['image' => 'admission-paths/prestasi.jpg']);

        $this->get(route('ppdb.index'))
            ->assertOk()
            ->assertSee('pl-paths-image', false)
            ->assertSee('class="pl-path-media"', false)
            ->assertSee('admission-paths/prestasi.jpg', false);
    }

    public function test_path_section_can_use_a_compact_list_with_fixed_columns(): void
    {
        AdmissionPath::factory()->create(['name' => 'Jalur Mutasi', 'detail_url' => '/halaman/mutasi']);

        $this->saveSections([['type' => 'ppdb_paths', 'heading' => 'Jalur Penerimaan', 'paths_layout' => 'list', 'paths_columns' => 2]]);

        $this->get(route('ppdb.index'))
            ->assertOk()
            ->assertSee('class="pl-cols pl-cols-2 pl-paths-list"', false)
            ->assertSee('pl-path-row', false)
            ->assertSee('class="pl-path-icon pl-path-icon-sm"', false)
            ->assertSee('href="/halaman/mutasi"', false);
    }

    public function test_unknown_path_layout_values_fall_back_to_safe_defaults(): void
    {
        AdmissionPath::factory()->create(['name' => 'Jalur Reguler']);

        $this->saveSections([['type' => 'ppdb_paths', 'heading' => 'Jalur Penerimaan', 'paths_layout' => 'carousel', 'paths_columns' => 9]]);

        $this->get(route('ppdb.index'))
            ->assertOk()
            ->assertSee('class="pl-cols pl-cols-3 pl-paths-icon"', false);
    }

    public function test_timeline_block_renders_its_points_in_order(): void
    {
        $this->saveSections([[
            'type' => 'ppdb_timeline',
            'heading' => 'Tanggal Penting',
            'intro' => 'Catat tanggal-tanggal berikut.',
            'timeline_layout' => 'alternate',
            'timeline_items' => [
                ['icon' => '📝', 'label' => '1 – 31 Januari', 'title' => 'Pendaftaran Gelombang 1', 'description' => 'Isi formulir online.', 'highlighted' => true],
                ['icon' => '', 'label' => '{year}', 'title' => 'Tes Seleksi', 'description' => ''],
                ['icon' => '', 'label' => '', 'title' => '', 'description' => 'Titik tanpa judul dilewati.'],
            ],
        ]]);

        $response = $this->get(route('ppdb.index'))
            ->assertOk()
            ->assertSee('class="pl-tl pl-tl-alternate', false)
            ->assertSeeInOrder(['Tanggal Penting', 'Catat tanggal-tanggal berikut.', '1 – 31 Januari', 'Pendaftaran Gelombang 1', 'Isi formulir online.', spmb_year_label(), 'Tes Seleksi'])
            ->assertSee('pl-tl-item pl-tl-item-active', false)
            ->assertDontSee('Titik tanpa judul dilewati.');

        // Titik tanpa emoji memakai nomor urutnya sebagai penanda.
        $this->assertMatchesRegularExpression('/<div class="pl-tl-marker"\s*>\s*2\s*<\/div>/', $response->getContent());
    }

    public function test_a_timeline_without_titled_points_is_left_out(): void
    {
        $this->saveSections([[
            'type' => 'ppdb_timeline',
            'heading' => 'Linimasa Kosong',
            'timeline_items' => [['icon' => '📅', 'label' => 'Januari', 'title' => '']],
        ]]);

        $this->get(route('ppdb.index'))
            ->assertOk()
            ->assertDontSee('Linimasa Kosong');
    }

    public function test_unknown_timeline_layout_falls_back_to_vertical(): void
    {
        $this->saveSections([[
            'type' => 'ppdb_timeline',
            'heading' => 'Tahapan',
            'timeline_layout' => 'spiral',
            'timeline_items' => [['title' => 'Daftar Ulang']],
        ]]);

        $this->get(route('ppdb.index'))
            ->assertOk()
            ->assertSee('class="pl-tl pl-tl-vertical', false);
    }

    public function test_data_sections_without_data_are_left_out(): void
    {
        $this->saveSections([
            ['type' => 'ppdb_quota', 'heading' => 'Kuota Tahun Ini'],
            ['type' => 'ppdb_faq', 'heading' => 'Pertanyaan Umum'],
            ['type' => 'ppdb_schedule', 'heading' => 'Jadwal Gelombang'],
        ]);

        $this->get(route('ppdb.index'))
            ->assertOk()
            ->assertDontSee('Kuota Tahun Ini')
            ->assertDontSee('Pertanyaan Umum')
            ->assertDontSee('Jadwal Gelombang');
    }

    public function test_quota_section_shows_filled_and_remaining_seats(): void
    {
        $year = AcademicYear::factory()->active()->create();
        $wave = RegistrationWave::factory()->open()->create([
            'academic_year_id' => $year->id,
            'institution_id' => $this->smp->id,
        ]);
        $this->smp->update(['quota' => 120]);

        foreach (['pending', 'accepted', 'rejected'] as $status) {
            SpmbRegistration::factory()->create([
                'institution_id' => $this->smp->id,
                'academic_year_id' => $year->id,
                'registration_wave_id' => $wave->id,
                'status' => $status,
            ]);
        }

        $this->saveSections([['type' => 'ppdb_quota', 'heading' => 'Kuota Tahun Ini', 'show_remaining' => true]]);

        $this->get(route('ppdb.index'))
            ->assertOk()
            ->assertSee('Kuota Tahun Ini')
            ->assertSeeInOrder(['SMP Contoh', '120', 'Terisi', '2', 'Sisa', '118'])
            ->assertDontSee('SMA Contoh');
    }

    public function test_quota_section_can_hide_the_remaining_seats(): void
    {
        $this->smp->update(['quota' => 120]);

        $this->saveSections([['type' => 'ppdb_quota', 'heading' => 'Kuota Tahun Ini', 'show_remaining' => false]]);

        $this->get(route('ppdb.index'))
            ->assertOk()
            ->assertSee('120')
            ->assertDontSee('Terisi');
    }

    public function test_faq_section_can_be_narrowed_to_one_category(): void
    {
        Faq::create(['question' => 'Kapan pendaftaran dibuka?', 'answer' => '<p>Januari.</p>', 'category' => 'SPMB', 'is_published' => true]);
        Faq::create(['question' => 'Apa saja ekskulnya?', 'answer' => '<p>Banyak.</p>', 'category' => 'Akademik', 'is_published' => true]);

        $this->saveSections([['type' => 'ppdb_faq', 'heading' => 'FAQ PPDB', 'faq_category' => 'SPMB']]);

        $this->get(route('ppdb.index'))
            ->assertOk()
            ->assertSee('Kapan pendaftaran dibuka?')
            ->assertDontSee('Apa saja ekskulnya?');
    }

    public function test_procedures_can_come_from_one_jenjang(): void
    {
        Setting::set('spmb_procedures', json_encode([['icon' => '📝', 'title' => 'Langkah Global', 'description' => '']]));
        $this->sma->update(['procedures' => [['icon' => '🧪', 'title' => 'Tes Psikologi SMA', 'description' => 'Tes minat bakat.']]]);

        $this->saveSections([
            ['type' => 'ppdb_procedures', 'heading' => 'Alur Umum'],
            ['type' => 'ppdb_procedures', 'heading' => 'Alur SMA', 'institution_id' => $this->sma->id],
        ]);

        $this->get(route('ppdb.index'))
            ->assertOk()
            ->assertSeeInOrder(['Alur Umum', 'Langkah Global', 'Alur SMA', 'Tes Psikologi SMA']);
    }

    public function test_schedule_section_groups_waves_by_jenjang(): void
    {
        $year = AcademicYear::factory()->active()->create();
        RegistrationWave::factory()->open()->create([
            'academic_year_id' => $year->id,
            'institution_id' => $this->sma->id,
            'name' => 'Gelombang Perdana SMA',
        ]);

        $this->saveSections([['type' => 'ppdb_schedule', 'heading' => 'Jadwal Gelombang']]);

        $this->get(route('ppdb.index'))
            ->assertOk()
            ->assertSeeInOrder(['Jadwal Gelombang', 'SMA Contoh', 'Gelombang Perdana SMA']);
    }

    public function test_meta_description_can_be_customized(): void
    {
        Setting::set(PpdbLanding::META_SETTING, 'Pendaftaran santri baru dibuka.');

        $this->get(route('ppdb.index'))
            ->assertOk()
            ->assertSee('<meta name="description" content="Pendaftaran santri baru dibuka.">', false);
    }

    /**
     * @param  list<array<string, mixed>>  $sections
     */
    private function saveSections(array $sections): void
    {
        Setting::set(PpdbLanding::SECTIONS_SETTING, json_encode($sections));
    }
}
