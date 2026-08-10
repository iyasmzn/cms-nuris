<?php

namespace App\Support;

/**
 * Jarak tepi kiri & kanan (margin) seluruh halaman publik.
 *
 * Rancangan aslinya memakai utilitas Tailwind `px-4 sm:px-6 lg:px-8` pada setiap
 * pembungkus `max-w-7xl`, sehingga jaraknya baku di semua halaman. Pengaturan ini
 * memindahkan angkanya ke satu tempat — satu nilai per ukuran layar — lalu
 * meneruskannya ke stylesheet lewat custom property `--page-gutter`, dan
 * menimpanya pada pembungkus tersebut.
 */
class PageGutter
{
    /**
     * Titik ubah (breakpoint) yang bisa diatur admin. `min_width` null berarti
     * nilai dasar (mobile-first) yang berlaku sebelum breakpoint pertama, dan
     * `default` adalah jarak rancangan bawaan agar pemasangan lama tampil persis
     * seperti sebelumnya. `reference_width` hanya dipakai untuk pratinjau.
     *
     * @var array<string, array{label: string, min_width: int|null, default: int, reference_width: int, hint: string}>
     */
    public const BREAKPOINTS = [
        'mobile' => [
            'label' => 'Ponsel',
            'min_width' => null,
            'default' => 16,
            'reference_width' => 390,
            'hint' => 'Lebar layar < 640px',
        ],
        'sm' => [
            'label' => 'Ponsel Besar',
            'min_width' => 640,
            'default' => 24,
            'reference_width' => 640,
            'hint' => 'Lebar layar ≥ 640px',
        ],
        'md' => [
            'label' => 'Tablet',
            'min_width' => 768,
            'default' => 24,
            'reference_width' => 768,
            'hint' => 'Lebar layar ≥ 768px',
        ],
        'lg' => [
            'label' => 'Laptop',
            'min_width' => 1024,
            'default' => 32,
            'reference_width' => 1024,
            'hint' => 'Lebar layar ≥ 1024px',
        ],
        'xl' => [
            'label' => 'Desktop',
            'min_width' => 1280,
            'default' => 32,
            'reference_width' => 1280,
            'hint' => 'Lebar layar ≥ 1280px',
        ],
    ];

    /** Batas jarak yang masuk akal, dalam piksel. */
    public const MIN = 0;

    public const MAX = 160;

    public const SETTING_PREFIX = 'page_gutter_';

    /**
     * Pembungkus konten baku website — `max-w-7xl mx-auto px-4 sm:px-6 lg:px-8`.
     * Ditulis sebagai selektor kelas Tailwind yang di-escape supaya jarak tepi
     * cukup diatur sekali, tanpa menyentuh puluhan berkas Blade.
     */
    public const CONTAINER_SELECTOR = '.px-4.sm\:px-6.lg\:px-8';

    public static function settingKey(string $breakpoint): string
    {
        return self::SETTING_PREFIX.$breakpoint;
    }

    /**
     * Jarak bawaan tiap breakpoint.
     *
     * @return array<string, int>
     */
    public static function defaults(): array
    {
        return array_map(
            fn (array $breakpoint): int => $breakpoint['default'],
            self::BREAKPOINTS
        );
    }

    /**
     * Jarak bawaan dengan kunci pengaturan, untuk seeder.
     *
     * @return array<string, int>
     */
    public static function settingDefaults(): array
    {
        $defaults = [];

        foreach (self::defaults() as $breakpoint => $value) {
            $defaults[self::settingKey($breakpoint)] = $value;
        }

        return $defaults;
    }

    /**
     * Jarak tersimpan tiap breakpoint, sudah disaring.
     *
     * @return array<string, int>
     */
    public static function values(): array
    {
        $values = [];

        foreach (self::BREAKPOINTS as $breakpoint => $config) {
            $values[$breakpoint] = self::sanitize(
                setting(self::settingKey($breakpoint), $config['default']),
                $breakpoint
            );
        }

        return $values;
    }

    /**
     * Saring nilai mentah (mis. dari form) menjadi piksel yang sah: kosong atau
     * bukan angka dikembalikan ke bawaan, selebihnya dijepit ke rentang wajar
     * supaya nilai aneh dari basis data tidak merusak tata letak.
     */
    public static function sanitize(mixed $value, string $breakpoint): int
    {
        $default = self::BREAKPOINTS[$breakpoint]['default'] ?? 0;

        if ($value === null || $value === '' || ! is_numeric($value)) {
            return $default;
        }

        return (int) max(self::MIN, min(self::MAX, round((float) $value)));
    }

    /**
     * CSS siap tempel: nilai dasar `--page-gutter`, satu media query per
     * breakpoint, lalu penerapannya ke pembungkus konten baku.
     */
    public static function css(): string
    {
        $values = self::values();
        $lines = [];

        foreach (self::BREAKPOINTS as $breakpoint => $config) {
            $value = $values[$breakpoint];

            if ($config['min_width'] === null) {
                $lines[] = ':root { --page-gutter: '.$value.'px; }';

                continue;
            }

            $lines[] = '@media (min-width: '.$config['min_width'].'px) { :root { --page-gutter: '.$value.'px; } }';
        }

        $lines[] = self::CONTAINER_SELECTOR.' { padding-left: var(--page-gutter); padding-right: var(--page-gutter); }';

        return implode("\n", $lines);
    }
}
