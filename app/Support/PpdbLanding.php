<?php

namespace App\Support;

use App\Models\AcademicYear;
use App\Models\AdmissionPath;
use App\Models\ContentSection;
use App\Models\Faq;
use App\Models\Institution;
use App\Models\RegistrationWave;
use App\Models\Setting;
use Illuminate\Support\Collection;

/**
 * Halaman depan PPDB (/ppdb) yang disusun admin: hero + deretan seksi.
 *
 * Seksinya memakai repeater blok yang sama dengan Halaman & Program, ditambah
 * jenis `ppdb_*` yang isinya ditarik dari data PPDB (jenjang, kuota, jalur,
 * gelombang, FAQ). Seksi data yang sedang kosong tidak dirender, jadi admin
 * tidak perlu mematikannya satu per satu.
 */
final class PpdbLanding
{
    public const HERO_SETTING = 'ppdb_landing_hero';

    public const SECTIONS_SETTING = 'ppdb_landing_sections';

    public const META_SETTING = 'ppdb_landing_meta_description';

    /**
     * Jenis seksi data PPDB → label di panel.
     *
     * @var array<string, string>
     */
    public const TYPES = [
        'ppdb_institutions' => '🏫  Pilihan Jenjang — kartu tiap jenjang beserta status pendaftarannya',
        'ppdb_quota' => '🎫  Kuota Penerimaan — daya tampung tiap jenjang dan sisanya',
        'ppdb_procedures' => '🧭  Alur Pendaftaran — langkah-langkah pendaftaran',
        'ppdb_paths' => '🛤️  Jalur Penerimaan — kartu dari menu Jalur Pendaftaran',
        'ppdb_schedule' => '🗓️  Jadwal Gelombang — gelombang tahun ajaran aktif tiap jenjang',
        'ppdb_fees' => '💰  Rincian Biaya — tabel biaya pendaftaran',
        'ppdb_requirements' => '📎  Persyaratan Dokumen — daftar berkas yang disiapkan',
        'ppdb_faq' => '❓  FAQ — pertanyaan umum dari menu FAQ',
    ];

    /**
     * Blok isian manual yang hanya tersedia di halaman PPDB → label di panel.
     *
     * @var array<string, string>
     */
    public const BLOCK_TYPES = [
        'ppdb_timeline' => '📍  Timeline — linimasa tanggal penting atau tahapan, diisi manual',
    ];

    /**
     * Tampilan blok Timeline → label di panel.
     *
     * @var array<string, string>
     */
    public const TIMELINE_LAYOUTS = [
        'vertical' => 'Vertikal — satu kolom memanjang ke bawah',
        'alternate' => 'Zig-zag — kiri-kanan bergantian di layar lebar',
        'horizontal' => 'Horizontal — berjajar mendatar, bisa digeser',
    ];

    /**
     * Seksi yang isinya bisa diambil dari Pengaturan PPDB atau dari satu jenjang.
     *
     * @var list<string>
     */
    public const JENJANG_SOURCED_TYPES = ['ppdb_procedures', 'ppdb_fees', 'ppdb_requirements'];

    /**
     * Tampilan kartu seksi Jalur Penerimaan → label di panel.
     *
     * @var array<string, string>
     */
    public const PATH_LAYOUTS = [
        'auto' => 'Otomatis — kartu bergambar bila ada jalur bergambar sampul, selain itu kartu ikon',
        'image' => 'Kartu Bergambar — gambar sampul lebar di atas kartu',
        'icon' => 'Kartu Ikon — ikon besar di pojok kartu, tanpa bidang gambar',
        'list' => 'Daftar Ringkas — baris berikon yang rapat',
    ];

    public const DEFAULT_PATH_COLUMNS = 3;

    /**
     * Nilai "Kartu Sebaris" untuk grid otomatis: baris diisi sebanyak kartu
     * yang muat di layar, seperti tampilan awal seksi Jenjang, Kuota, dan Alur.
     */
    public const AUTO_COLUMNS = 'auto';

    /**
     * Jumlah kartu sebaris yang dipakai seksi; nilai asing kembali ke bawaannya.
     *
     * @param  array<string, mixed>  $block
     */
    public static function columns(array $block, string $key, int $default): int
    {
        $columns = (int) ($block[$key] ?? $default);

        return isset(ContentSection::ITEM_COLUMNS[$columns]) ? $columns : $default;
    }

    /**
     * Pilihan "Kartu Sebaris" seksi yang bisa memakai grid otomatis → label.
     *
     * @return array<int|string, string>
     */
    public static function autoColumnOptions(): array
    {
        return [self::AUTO_COLUMNS => 'Otomatis — sebanyak yang muat di layar'] + ContentSection::ITEM_COLUMNS;
    }

    /**
     * Kelas grid kartu seksi: kolom tetap bila "Kartu Sebaris" dipilih, selain
     * itu (Otomatis, kosong, atau nilai asing) grid otomatis.
     *
     * @param  array<string, mixed>  $block
     */
    public static function gridClass(array $block, string $key): string
    {
        $columns = (int) ($block[$key] ?? 0);

        return isset(ContentSection::ITEM_COLUMNS[$columns]) ? 'pl-cols pl-cols-'.$columns : 'pl-grid';
    }

    /**
     * Tampilan kartu jalur yang benar-benar dipakai. "Otomatis" memilih kartu
     * bergambar hanya bila ada jalur yang punya gambar sampul, supaya jalur
     * tanpa gambar tidak tampil sebagai bidang kosong berisi ikon kecil.
     *
     * @param  array<string, mixed>  $block
     * @param  Collection<int, AdmissionPath>  $paths
     */
    public static function pathLayout(array $block, Collection $paths): string
    {
        $layout = (string) ($block['paths_layout'] ?? 'auto');

        if (! isset(self::PATH_LAYOUTS[$layout]) || $layout === 'auto') {
            return $paths->contains(fn (AdmissionPath $path): bool => filled($path->image)) ? 'image' : 'icon';
        }

        return $layout;
    }

    public static function isDataType(?string $type): bool
    {
        return isset(self::TYPES[(string) $type]);
    }

    /**
     * Seksi data maupun blok isian khusus PPDB — keduanya punya teks pengantar.
     */
    public static function isPpdbType(?string $type): bool
    {
        return isset(self::blockTypes()[(string) $type]);
    }

    /**
     * Semua jenis blok tambahan builder PPDB, untuk pilihan "Jenis Blok".
     *
     * @return array<string, string>
     */
    public static function blockTypes(): array
    {
        return self::TYPES + self::BLOCK_TYPES;
    }

    /**
     * Tampilan Timeline yang dipakai; nilai asing kembali ke vertikal.
     *
     * @param  array<string, mixed>  $block
     */
    public static function timelineLayout(array $block): string
    {
        $layout = (string) ($block['timeline_layout'] ?? 'vertical');

        return isset(self::TIMELINE_LAYOUTS[$layout]) ? $layout : 'vertical';
    }

    /**
     * Partial Blade yang merender isi tiap jenis seksi khusus PPDB.
     *
     * @return array<string, string>
     */
    public static function views(): array
    {
        return collect(self::blockTypes())
            ->mapWithKeys(fn (string $label, string $type): array => [$type => 'ppdb.landing.'.str_replace('ppdb_', '', $type)])
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    public static function defaultHero(): array
    {
        return [
            'badge' => 'PPDB / SPMB {year}',
            'title' => 'Penerimaan Peserta Didik Baru',
            'highlight' => 'Pilih Jenjang Pendidikan',
            'subtitle' => '{site} membuka pendaftaran untuk beberapa jenjang. Pilih jenjang yang dituju untuk melihat prosedur, jadwal gelombang, biaya, dan formulir pendaftaran.',
            'primary_label' => 'Daftar Sekarang',
            'primary_url' => '#jenjang',
            'secondary_label' => 'Cek Status Pendaftaran',
            'secondary_url' => '/ppdb/status',
            'media_type' => 'image',
        ];
    }

    /**
     * Hero tersimpan, dengan bidang yang belum pernah diisi memakai bawaan.
     *
     * @return array<string, mixed>
     */
    public static function hero(): array
    {
        $saved = json_decode((string) Setting::get(self::HERO_SETTING, ''), true);

        return is_array($saved) ? [...self::defaultHero(), ...$saved] : self::defaultHero();
    }

    /**
     * Seksi bawaan: meniru susunan halaman pendaftaran pada umumnya, dengan
     * latar selang-seling agar antar seksi tidak menyatu.
     *
     * @return list<array<string, mixed>>
     */
    public static function defaultSections(): array
    {
        return [
            [
                'type' => 'ppdb_institutions',
                'eyebrow' => 'Pilih Jenjang',
                'heading' => 'Jenjang Pendidikan',
                'intro' => 'Setiap jenjang memiliki gelombang, jalur, dan formulir pendaftarannya sendiri.',
                'anchor' => 'jenjang',
                'heading_align' => 'center',
                'background' => 'default',
                'padding' => 'md',
            ],
            [
                'type' => 'ppdb_quota',
                'eyebrow' => 'Kuota Penerimaan',
                'heading' => 'Kuota Tersedia Tahun Ini',
                'intro' => 'Penerimaan dibuka dengan kuota terbatas sesuai daya tampung dan standar mutu pendidikan tiap jenjang.',
                'anchor' => 'kuota',
                'heading_align' => 'center',
                'show_remaining' => true,
                'background' => 'alt',
                'padding' => 'md',
            ],
            [
                'type' => 'ppdb_procedures',
                'eyebrow' => 'Proses Pendaftaran',
                'heading' => 'Bagaimana Cara Mendaftar?',
                'intro' => 'Ikuti langkah-langkah berikut untuk mendaftarkan diri sebagai calon peserta didik baru.',
                'anchor' => 'alur',
                'heading_align' => 'center',
                'background' => 'default',
                'padding' => 'md',
            ],
            [
                'type' => 'ppdb_paths',
                'eyebrow' => 'Jalur Penerimaan',
                'heading' => 'Berbagai Jalur Penerimaan',
                'intro' => 'Beberapa jalur disediakan agar calon peserta didik dengan latar belakang dan kemampuan yang beragam mendapat kesempatan yang sama.',
                'anchor' => 'jalur',
                'heading_align' => 'center',
                'paths_layout' => 'auto',
                'paths_columns' => self::DEFAULT_PATH_COLUMNS,
                'background' => 'alt',
                'padding' => 'md',
            ],
            [
                'type' => 'ppdb_schedule',
                'eyebrow' => 'Jadwal',
                'heading' => 'Jadwal Gelombang Pendaftaran',
                'anchor' => 'jadwal',
                'heading_align' => 'center',
                'background' => 'default',
                'padding' => 'md',
            ],
            [
                'type' => 'ppdb_faq',
                'eyebrow' => 'FAQ',
                'heading' => 'Punya Pertanyaan? Kami Punya Jawabannya',
                'anchor' => 'faq',
                'heading_align' => 'center',
                'background' => 'alt',
                'padding' => 'md',
            ],
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function sections(): array
    {
        $saved = json_decode((string) Setting::get(self::SECTIONS_SETTING, ''), true);

        return is_array($saved) ? array_values($saved) : self::defaultSections();
    }

    /**
     * Whether the admin has saved the page at least once. Until then a
     * single-jenjang school keeps its one-click redirect to that jenjang.
     */
    public static function isCustomized(): bool
    {
        return Setting::get(self::SECTIONS_SETTING) !== null;
    }

    public static function metaDescription(): ?string
    {
        $description = trim((string) Setting::get(self::META_SETTING, ''));

        return $description !== '' ? $description : null;
    }

    /**
     * Isi placeholder teks hero: {year} tahun ajaran PPDB, {site} nama situs.
     */
    public static function fill(?string $text): string
    {
        return strtr((string) $text, [
            '{year}' => spmb_year_label(),
            '{site}' => (string) setting('site_name', config('app.name')),
        ]);
    }

    /**
     * Seksi siap render. Seksi data PPDB dibekali isinya di kunci `data`, dan
     * yang isinya kosong dibuang — kecuali Pilihan Jenjang, yang menampilkan
     * pesan "belum ada jenjang" sendiri.
     *
     * @return list<array<string, mixed>>
     */
    public static function renderableSections(): array
    {
        $institutions = null;
        $loadInstitutions = function () use (&$institutions): Collection {
            return $institutions ??= Institution::query()->active()->ordered()->withQuotaUsage()->get();
        };

        $sections = [];

        foreach (self::sections() as $block) {
            if (! is_array($block)) {
                continue;
            }

            $type = (string) ($block['type'] ?? '');

            if ($type === 'ppdb_timeline') {
                $block['timeline_items'] = array_values(array_filter(
                    (array) ($block['timeline_items'] ?? []),
                    fn ($item): bool => is_array($item) && filled($item['title'] ?? null),
                ));

                if ($block['timeline_items'] !== []) {
                    $sections[] = $block;
                }

                continue;
            }

            if (! self::isDataType($type)) {
                $sections[] = $block;

                continue;
            }

            $data = match ($type) {
                'ppdb_institutions' => $loadInstitutions(),
                'ppdb_quota' => $loadInstitutions()->filter(fn (Institution $institution): bool => $institution->hasQuota())->values(),
                'ppdb_procedures' => self::sourceInstitution($block)?->resolvedProcedures()
                    ?: (json_decode((string) Setting::get('spmb_procedures', ''), true) ?: Institution::defaultProcedures()),
                'ppdb_fees' => self::sourceInstitution($block)?->resolvedFees()
                    ?: (json_decode((string) Setting::get('spmb_fees', ''), true) ?: []),
                'ppdb_requirements' => self::requirements($block),
                'ppdb_paths' => AdmissionPath::query()->active()->ordered()->with('institutions')->get(),
                'ppdb_schedule' => self::schedule($loadInstitutions()),
                'ppdb_faq' => Faq::published()
                    ->when(filled($block['faq_category'] ?? null), fn ($query) => $query->where('category', $block['faq_category']))
                    ->get(),
            };

            if ($type !== 'ppdb_institutions' && blank($data)) {
                continue;
            }

            $block['data'] = $data;
            $sections[] = $block;
        }

        return $sections;
    }

    /**
     * Jenjang yang dipilih sebagai sumber isi seksi, atau null untuk memakai
     * Pengaturan PPDB umum.
     *
     * @param  array<string, mixed>  $block
     */
    private static function sourceInstitution(array $block): ?Institution
    {
        $id = $block['institution_id'] ?? null;

        return filled($id) ? Institution::query()->find($id) : null;
    }

    /**
     * @param  array<string, mixed>  $block
     * @return list<string>
     */
    private static function requirements(array $block): array
    {
        $institution = self::sourceInstitution($block);

        if ($institution !== null) {
            return $institution->resolvedRequirements();
        }

        $requirements = json_decode((string) Setting::get('spmb_requirements', ''), true) ?: [];

        return array_values(array_filter(
            array_map(static fn ($requirement): string => trim((string) $requirement), $requirements),
            static fn (string $requirement): bool => $requirement !== '',
        ));
    }

    /**
     * Gelombang tahun ajaran aktif, dikelompokkan per jenjang sesuai urutan
     * jenjangnya. Jenjang tanpa gelombang tidak ikut.
     *
     * @param  Collection<int, Institution>  $institutions
     * @return Collection<int, array{institution: Institution, waves: Collection<int, RegistrationWave>}>
     */
    private static function schedule(Collection $institutions): Collection
    {
        $year = AcademicYear::active();

        if ($year === null) {
            return collect();
        }

        $waves = RegistrationWave::query()
            ->where('academic_year_id', $year->id)
            ->whereIn('institution_id', $institutions->modelKeys())
            ->where('is_active', true)
            ->orderBy('start_date')
            ->get()
            ->groupBy('institution_id');

        return $institutions
            ->filter(fn (Institution $institution): bool => $waves->has($institution->id))
            ->map(fn (Institution $institution): array => [
                'institution' => $institution,
                'waves' => $waves->get($institution->id),
            ])
            ->values();
    }
}
