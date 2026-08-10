<?php

namespace App\Support;

/**
 * Lebar maksimum isi halaman publik.
 *
 * Rancangan aslinya mengunci setiap pembungkus konten pada `max-w-7xl`
 * (80rem = 1280px) lalu memusatkannya, sehingga di monitor lebar tersisa ruang
 * kosong di kiri-kanan. Pengaturan ini memindahkan angka itu ke satu tempat dan
 * meneruskannya lewat custom property `--page-max-width`, berpasangan dengan
 * jarak tepi di [[PageGutter]].
 */
class PageWidth
{
    /**
     * Pilihan lebar. `css` adalah nilai `max-width` yang dipakai stylesheet, dan
     * `pixels` null berarti tanpa batas (penuh layar) — dipakai pratinjau.
     *
     * @var array<string, array{label: string, css: string, pixels: int|null}>
     */
    public const WIDTHS = [
        '4xl' => ['label' => 'Sempit — 896px', 'css' => '56rem', 'pixels' => 896],
        '5xl' => ['label' => 'Agak Sempit — 1024px', 'css' => '64rem', 'pixels' => 1024],
        '6xl' => ['label' => 'Sedang — 1152px', 'css' => '72rem', 'pixels' => 1152],
        '7xl' => ['label' => 'Bawaan — 1280px', 'css' => '80rem', 'pixels' => 1280],
        'wide' => ['label' => 'Lebar — 1440px', 'css' => '90rem', 'pixels' => 1440],
        'wider' => ['label' => 'Sangat Lebar — 1600px', 'css' => '100rem', 'pixels' => 1600],
        'full' => ['label' => 'Penuh Layar — tanpa batas', 'css' => 'none', 'pixels' => null],
    ];

    /**
     * Lebar bawaan, yaitu `max-w-7xl` yang dipakai rancangan sebelum pengaturan
     * ini ada. Jadi pemasangan lama tampil persis seperti sebelumnya.
     */
    public const DEFAULT_WIDTH = '7xl';

    public const SETTING_KEY = 'page_max_width';

    /**
     * Pilihan untuk dropdown di panel.
     *
     * @return array<string, string>
     */
    public static function options(): array
    {
        return array_map(
            fn (array $width): string => $width['label'],
            self::WIDTHS
        );
    }

    /**
     * Pilihan tersimpan, dikembalikan ke bawaan bila nilainya tidak dikenal.
     */
    public static function key(): string
    {
        return self::sanitize(setting(self::SETTING_KEY, self::DEFAULT_WIDTH));
    }

    /**
     * Saring nilai mentah (mis. dari form) menjadi salah satu pilihan yang sah.
     */
    public static function sanitize(mixed $width): string
    {
        $width = is_string($width) ? $width : '';

        return array_key_exists($width, self::WIDTHS) ? $width : self::DEFAULT_WIDTH;
    }

    /**
     * Lebar dalam piksel, atau null bila penuh layar.
     */
    public static function pixels(?string $width = null): ?int
    {
        $width = $width !== null ? self::sanitize($width) : self::key();

        return self::WIDTHS[$width]['pixels'];
    }

    /**
     * CSS siap tempel: nilai `--page-max-width` beserta penerapannya ke
     * pembungkus konten baku (`max-w-7xl mx-auto`).
     */
    public static function css(): string
    {
        $value = self::WIDTHS[self::key()]['css'];

        return implode("\n", [
            ':root { --page-max-width: '.$value.'; }',
            '.max-w-7xl.mx-auto { max-width: var(--page-max-width); }',
        ]);
    }
}
