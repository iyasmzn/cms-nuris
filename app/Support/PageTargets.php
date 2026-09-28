<?php

namespace App\Support;

use App\Models\Institution;
use App\Models\Program;
use App\Models\SpmbRegistration;
use App\Models\StaticPage;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;

/**
 * Halaman publik yang bisa dijadikan sasaran oleh admin — misalnya floating
 * button yang hanya boleh muncul di halaman PPDB satu jenjang.
 *
 * Setiap sasaran berupa kunci teks:
 * - `group:{nama}` untuk satu keluarga route, mis. `group:ppdb` (semua PPDB);
 * - `ppdb:{id}` untuk halaman PPDB satu jenjang, termasuk halaman status
 *   pendaftaran & pembayaran milik pendaftar jenjang itu;
 * - `page:{id}` untuk satu halaman statis;
 * - `program:{id}` untuk satu halaman program.
 *
 * Kunci record memakai id, bukan slug, supaya sasaran tetap berlaku ketika
 * slug halaman diganti.
 */
class PageTargets
{
    /**
     * Keluarga route yang bisa dipilih sekaligus.
     *
     * @var array<string, array{label: string, routes: list<string>}>
     */
    public const GROUPS = [
        'home' => ['label' => 'Beranda', 'routes' => ['home']],
        'blog' => ['label' => 'Blog & Berita', 'routes' => ['blog.*']],
        'programs' => ['label' => 'Program (daftar & detail)', 'routes' => ['programs.*']],
        'ppdb' => ['label' => 'PPDB (semua jenjang)', 'routes' => ['ppdb.*']],
        'events' => ['label' => 'Kegiatan', 'routes' => ['events.*']],
        'stories' => ['label' => 'Cerita Santri', 'routes' => ['stories.*']],
        'teachers' => ['label' => 'Guru', 'routes' => ['teachers.*']],
        'gallery' => ['label' => 'Galeri', 'routes' => ['gallery.*']],
        'downloads' => ['label' => 'Unduhan', 'routes' => ['downloads.*']],
        'contact' => ['label' => 'Kontak', 'routes' => ['contact.*']],
        'pages' => ['label' => 'Halaman statis (semua)', 'routes' => ['page.show']],
        'account' => ['label' => 'Akun (masuk, daftar, profil)', 'routes' => ['login', 'register', 'profile.*', 'verification.*']],
    ];

    /**
     * Semua sasaran, dikelompokkan untuk select Filament.
     *
     * @return array<string, array<string, string>>
     */
    public static function options(): array
    {
        return once(fn (): array => array_filter([
            'Grup halaman' => collect(self::GROUPS)
                ->mapWithKeys(fn (array $group, string $key): array => ["group:{$key}" => $group['label']])
                ->all(),
            'PPDB per jenjang' => Institution::query()
                ->ordered()
                ->get(['id', 'name', 'is_active'])
                ->mapWithKeys(fn (Institution $institution): array => [
                    "ppdb:{$institution->id}" => "PPDB {$institution->name}".($institution->is_active ? '' : ' (nonaktif)'),
                ])
                ->all(),
            'Halaman statis' => StaticPage::query()
                ->ordered()
                ->pluck('title', 'id')
                ->mapWithKeys(fn (string $title, int $id): array => ["page:{$id}" => $title])
                ->all(),
            'Program' => Program::query()
                ->ordered()
                ->pluck('title', 'id')
                ->mapWithKeys(fn (string $title, int $id): array => ["program:{$id}" => "Program {$title}"])
                ->all(),
        ]));
    }

    /**
     * Label tiap sasaran tanpa pengelompokan.
     *
     * @return array<string, string>
     */
    public static function labels(): array
    {
        return array_merge(...array_values(self::options()));
    }

    /**
     * Buang sasaran yang record-nya sudah tidak ada, mis. jenjang yang
     * dihapus. Tanpa ini select Filament menolak menyimpan form edit karena
     * nilainya tidak ada di daftar pilihan.
     *
     * @param  array<int, string>  $targets
     * @return list<string>
     */
    public static function onlyKnown(array $targets): array
    {
        return array_values(array_intersect($targets, array_keys(self::labels())));
    }

    /**
     * Sasaran yang cocok dengan halaman yang sedang dibuka.
     *
     * @return list<string>
     */
    public static function forRequest(Request $request): array
    {
        $route = $request->route();

        if (! $route instanceof Route) {
            return [];
        }

        $targets = collect(self::GROUPS)
            ->filter(fn (array $group): bool => $request->routeIs(...$group['routes']))
            ->keys()
            ->map(fn (string $key): string => "group:{$key}")
            ->all();

        if (($recordTarget = self::recordTarget($route)) !== null) {
            $targets[] = $recordTarget;
        }

        return $targets;
    }

    /**
     * Sasaran record untuk route yang menampilkan satu record tertentu.
     *
     * Parameter route dicek tipenya karena bisa masih berupa teks mentah bila
     * route model binding gagal dan halaman error yang sedang dirender.
     */
    private static function recordTarget(Route $route): ?string
    {
        switch ($route->getName()) {
            case 'ppdb.show':
                $institution = $route->parameter('institution');

                return $institution instanceof Institution ? "ppdb:{$institution->getKey()}" : null;

            case 'ppdb.payment':
                $registration = $route->parameter('registration');

                return $registration instanceof SpmbRegistration && filled($registration->institution_id)
                    ? "ppdb:{$registration->institution_id}"
                    : null;

            case 'programs.show':
                $program = $route->parameter('program');

                return $program instanceof Program ? "program:{$program->getKey()}" : null;

            case 'page.show':
                $pageId = StaticPage::query()->where('slug', $route->parameter('slug'))->value('id');

                return $pageId !== null ? "page:{$pageId}" : null;

            default:
                return null;
        }
    }
}
