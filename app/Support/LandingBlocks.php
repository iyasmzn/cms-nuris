<?php

namespace App\Support;

use App\Models\AlumniStat;
use App\Models\AlumniUniversity;
use App\Models\ContentSection;
use App\Models\Event;
use App\Models\Faq;
use App\Models\Greeting;
use App\Models\Institution;
use App\Models\Media;
use App\Models\Post;
use App\Models\Program;
use App\Models\RegistrationWave;
use App\Models\Stat;
use App\Models\Testimonial;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * Seksi halaman depan yang bisa dipasang sebagai blok di Halaman Statis dan
 * Program, supaya admin bisa menyusun halaman yang mirip halaman depan.
 *
 * Isinya ditarik dari menu yang sama dengan halaman depan (Statistik, Program,
 * Artikel, FAQ, …) dan tampilannya memakai partial isi yang sama
 * (`sections/bodies/*`), tetapi judul, latar, dan jaraknya diatur per blok.
 * Blok yang datanya sedang kosong tidak dirender.
 */
final class LandingBlocks
{
    /**
     * Jenis blok data → label di panel.
     *
     * @var array<string, string>
     */
    public const TYPES = [
        'data_stats' => '📊  Statistik — angka capaian dari menu Statistik',
        'data_greetings' => '👨‍💼  Sambutan Tokoh — slider sambutan dari menu Sambutan Tokoh',
        'data_programs' => '🎓  Program — kartu dari menu Program',
        'data_events' => '📅  Agenda Kegiatan — kegiatan yang akan datang',
        'data_gallery' => '📷  Galeri Foto — media yang ditandai "Tampil di Galeri"',
        'data_posts' => '📰  Blog & Berita — artikel terbaru',
        'data_alumni' => '🏛️  Jejak Alumni — capaian & logo kampus tujuan alumni',
        'data_testimonials' => '💬  Kesan & Pesan — dari menu Kesan & Pesan',
        'data_faq' => '❓  FAQ — pertanyaan umum dari menu FAQ',
        'data_contact' => '📞  Kontak — kartu kontak, peta, dan tombol WhatsApp',
        'data_quick_links' => '🔗  Tautan Cepat — ubin tautan dari Pengaturan Tautan Cepat',
        'data_spmb' => '📋  Kartu SPMB — ajakan mendaftar, tampil saat pendaftaran dibuka',
        'data_spmb_steps' => '📝  Tahapan SPMB — langkah pendaftaran dari Pengaturan SPMB',
    ];

    /**
     * Tempat admin mengelola isi tiap jenis blok, untuk petunjuk di panel.
     *
     * @var array<string, string>
     */
    public const SOURCES = [
        'data_stats' => 'Profil Sekolah → Statistik',
        'data_greetings' => 'Profil Sekolah → Sambutan Tokoh',
        'data_programs' => 'Konten → Program (yang dipublikasikan)',
        'data_events' => 'Konten → Kegiatan / Event (yang akan datang)',
        'data_gallery' => 'Media & Unduhan → Media Library (bertanda "Tampil di Galeri")',
        'data_posts' => 'Konten → Blog & Berita (yang dipublikasikan)',
        'data_alumni' => 'Profil Sekolah → Statistik Alumni & Kampus Alumni',
        'data_testimonials' => 'Profil Sekolah → Kesan & Pesan',
        'data_faq' => 'Profil Sekolah → FAQ',
        'data_contact' => 'Pengaturan → Pengaturan Umum (kontak & peta)',
        'data_quick_links' => 'Pengaturan → Tautan Cepat',
        'data_spmb' => 'PPDB / SPMB → Pengaturan PPDB & Jenjang / Unit',
        'data_spmb_steps' => 'PPDB / SPMB → Pengaturan PPDB (alur pendaftaran)',
    ];

    /**
     * Jumlah item bawaan tiap jenis yang bisa dibatasi.
     *
     * @var array<string, int>
     */
    public const DEFAULT_LIMITS = [
        'data_programs' => 6,
        'data_events' => 3,
        'data_gallery' => 6,
        'data_posts' => 7,
        'data_testimonials' => 6,
    ];

    /**
     * Pilihan "Jumlah Ditampilkan".
     *
     * @var array<int, string>
     */
    public const LIMITS = [
        3 => '3 item',
        4 => '4 item',
        6 => '6 item',
        7 => '7 item',
        8 => '8 item',
        9 => '9 item',
        12 => '12 item',
    ];

    /**
     * Jenis yang isinya berupa grid kartu dengan pilihan "Kartu Sebaris".
     *
     * @var list<string>
     */
    public const GRID_TYPES = ['data_programs', 'data_events', 'data_gallery', 'data_posts', 'data_testimonials'];

    /**
     * Jenis yang bisa disaring per kategori, beserta model sumber kategorinya.
     *
     * @var array<string, class-string<Model>>
     */
    public const CATEGORY_SOURCES = [
        'data_programs' => Program::class,
        'data_events' => Event::class,
        'data_posts' => Post::class,
        'data_faq' => Faq::class,
    ];

    /**
     * Jenis yang punya tautan "Lihat Semua" ke halaman daftarnya → [route, teks].
     *
     * @var array<string, array{0: string, 1: string}>
     */
    public const MORE_LINKS = [
        'data_programs' => ['programs.index', 'Lihat Semua Program'],
        'data_events' => ['events.index', 'Semua Kegiatan'],
        'data_gallery' => ['gallery.index', 'Semua Foto'],
        'data_posts' => ['blog.index', 'Lihat Semua Artikel'],
    ];

    public const DEFAULT_COLUMNS = 3;

    public static function isDataType(?string $type): bool
    {
        return isset(self::TYPES[(string) $type]);
    }

    /**
     * Jenis blok → partial pembungkus isinya. Satu partial melayani semuanya;
     * ia meneruskan ke `sections/bodies/*` sesuai jenisnya.
     *
     * @return array<string, string>
     */
    public static function views(): array
    {
        return array_fill_keys(array_keys(self::TYPES), 'partials.landing-block-body');
    }

    /**
     * Jumlah item yang ditampilkan; nilai asing kembali ke bawaan jenisnya.
     *
     * @param  array<string, mixed>  $block
     */
    public static function limit(array $block): int
    {
        $limit = (int) ($block['items_limit'] ?? 0);

        return isset(self::LIMITS[$limit]) ? $limit : (self::DEFAULT_LIMITS[$block['type'] ?? ''] ?? 6);
    }

    /**
     * Kelas grid kartu blok: kolom tetap sesuai "Kartu Sebaris" (bawaan 3).
     *
     * @param  array<string, mixed>  $block
     */
    public static function gridClass(array $block): string
    {
        $columns = (int) ($block['data_columns'] ?? self::DEFAULT_COLUMNS);

        return 'ldb-grid ldb-cols-'.(isset(ContentSection::ITEM_COLUMNS[$columns]) ? $columns : self::DEFAULT_COLUMNS);
    }

    /**
     * Kategori yang dipakai record sumber suatu jenis blok, untuk pilihan saring.
     *
     * @return array<string, string>
     */
    public static function categoryOptions(?string $type): array
    {
        $model = self::CATEGORY_SOURCES[(string) $type] ?? null;

        if ($model === null) {
            return [];
        }

        return $model::query()
            ->whereNotNull('category')
            ->where('category', '!=', '')
            ->distinct()
            ->orderBy('category')
            ->pluck('category', 'category')
            ->all();
    }

    /**
     * Tautan cepat aktif dari Pengaturan Tautan Cepat, atau daftar bawaannya.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public static function quickLinks(): Collection
    {
        return collect(json_decode((string) setting('quick_links', ''), true) ?: [
            ['icon' => '📋', 'label' => 'SPMB', 'url' => '#spmb', 'is_active' => true],
            ['icon' => '📥', 'label' => 'Unduhan', 'url' => '/unduhan', 'is_active' => true],
            ['icon' => '📅', 'label' => 'Jadwal', 'url' => '#jadwal', 'is_active' => true],
            ['icon' => '🏆', 'label' => 'Prestasi', 'url' => '#prestasi', 'is_active' => true],
            ['icon' => '👥', 'label' => 'Alumni', 'url' => '#alumni', 'is_active' => true],
            ['icon' => '📞', 'label' => 'Kontak', 'url' => '#kontak', 'is_active' => true],
        ])->where('is_active', true)->values();
    }

    /**
     * Langkah pendaftaran dari Pengaturan SPMB, atau empat langkah bawaannya.
     *
     * @return list<array<string, mixed>>
     */
    public static function spmbProcedures(): array
    {
        $saved = json_decode((string) setting('spmb_procedures', ''), true);

        if (is_array($saved) && $saved !== []) {
            return array_values($saved);
        }

        $announceDate = RegistrationWave::relevant()?->announcement_date;
        $announceText = $announceDate ? 'pada tanggal '.$announceDate->locale('id')->translatedFormat('d M Y').' ' : '';

        return [
            ['icon' => '📝', 'title' => 'Isi Formulir Online', 'description' => 'Kunjungi halaman PPDB dan isi formulir pendaftaran secara lengkap dan benar melalui portal SPMB.'],
            ['icon' => '📁', 'title' => 'Siapkan Berkas', 'description' => 'Persiapkan dokumen yang dipersyaratkan: ijazah/SHUN, rapor, dan pas foto terbaru.'],
            ['icon' => '✅', 'title' => 'Verifikasi Berkas', 'description' => 'Berkas diverifikasi oleh panitia. Pantau status pendaftaran melalui akun Anda.'],
            ['icon' => '🎉', 'title' => 'Pengumuman', 'description' => 'Hasil seleksi diumumkan '.$announceText.'melalui portal resmi sekolah.'],
        ];
    }

    /**
     * Blok siap render. Blok data dibekali isinya di kunci `data` dan yang
     * isinya kosong dibuang; blok isian manual diteruskan apa adanya.
     *
     * @param  array<int, mixed>|null  $blocks
     * @param  Program|null  $currentProgram  program yang sedang dibuka, agar tidak ikut terdaftar di blok Program
     * @return list<array<string, mixed>>
     */
    public static function prepare(?array $blocks, ?Program $currentProgram = null): array
    {
        $prepared = [];

        foreach ($blocks ?? [] as $block) {
            if (! is_array($block)) {
                continue;
            }

            $type = (string) ($block['type'] ?? '');

            if (! self::isDataType($type)) {
                $prepared[] = $block;

                continue;
            }

            $data = self::load($type, $block, $currentProgram);

            if (self::isEmpty($type, $data)) {
                continue;
            }

            $block['data'] = $data;
            $prepared[] = $block;
        }

        return $prepared;
    }

    /**
     * @param  array<string, mixed>  $block
     */
    private static function load(string $type, array $block, ?Program $currentProgram): mixed
    {
        $category = filled($block['data_category'] ?? null) ? (string) $block['data_category'] : null;
        $limit = self::limit($block);

        return match ($type) {
            'data_stats' => Stat::ordered()->get(),
            'data_greetings' => Greeting::published()->get(),
            'data_programs' => Program::published()
                ->ordered()
                ->when($category, fn ($query) => $query->where('category', $category))
                ->when($currentProgram, fn ($query) => $query->whereKeyNot($currentProgram->getKey()))
                ->limit($limit)
                ->get(),
            'data_events' => Event::published()
                ->upcoming()
                ->ordered()
                ->when($category, fn ($query) => $query->where('category', $category))
                ->limit($limit)
                ->get(),
            'data_gallery' => Media::inGallery()
                ->when(filled($block['data_album'] ?? null), fn ($query) => $query->where('album_id', $block['data_album']))
                ->limit($limit)
                ->get(),
            'data_posts' => Post::published()
                ->latest('published_at')
                ->when($category, fn ($query) => $query->byCategory($category))
                ->limit($limit)
                ->get(),
            'data_alumni' => [
                'stats' => AlumniStat::ordered()->get(),
                'universities' => AlumniUniversity::active()->get(),
            ],
            'data_testimonials' => Testimonial::published()->limit($limit)->get(),
            'data_faq' => Faq::published()
                ->when($category, fn ($query) => $query->where('category', $category))
                ->get(),
            'data_contact' => contact_items(),
            'data_quick_links' => self::quickLinks(),
            'data_spmb' => self::spmbInstitutions(),
            'data_spmb_steps' => self::spmbProcedures(),
        };
    }

    /**
     * Jenjang untuk Kartu SPMB; kosong bila tidak ada yang sedang membuka
     * pendaftaran, sehingga kartunya ikut tersembunyi seperti di halaman depan.
     *
     * @return Collection<int, Institution>
     */
    private static function spmbInstitutions(): Collection
    {
        $institutions = Institution::query()->active()->ordered()->withQuotaUsage()->get();

        return $institutions->contains(fn (Institution $institution): bool => $institution->registrationOpen())
            ? $institutions
            : collect();
    }

    private static function isEmpty(string $type, mixed $data): bool
    {
        if ($type === 'data_alumni') {
            return $data['stats']->isEmpty() && $data['universities']->isEmpty();
        }

        return blank($data);
    }
}
