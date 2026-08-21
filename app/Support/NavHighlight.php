<?php

namespace App\Support;

/**
 * Warna sorotan (highlight) menu navigasi.
 *
 * Menu atas menyoroti dirinya di dua keadaan: saat kursor melewatinya, dan saat
 * halaman — atau seksi — yang ditunjuknya sedang dibuka. Sebelumnya sorotan itu
 * selalu ikut warna utama website; pengaturan ini memberinya warna sendiri tanpa
 * memaksa admin mengganti warna utama.
 *
 * Nilainya diteruskan lewat `--nav-highlight` beserta empat turunannya, sebab
 * satu warna saja tidak cukup: bar putih butuh warna aslinya, sedangkan bar
 * transparan di atas hero gelap dan menu mobile butuh versi yang dicerahkan agar
 * tetap terbaca berapa pun warna yang dipilih.
 */
class NavHighlight
{
    public const SETTING_KEY = 'nav_highlight_color';

    /**
     * Bawaannya kosong, yang berarti "ikut warna utama website" — jadi pemasangan
     * lama tampil persis seperti sebelum pengaturan ini ada.
     */
    public const DEFAULT_COLOR = '';

    /**
     * Warna pilihan admin, atau null bila mengikuti warna utama.
     */
    public static function color(): ?string
    {
        $color = self::sanitize(setting(self::SETTING_KEY, self::DEFAULT_COLOR));

        return $color === '' ? null : $color;
    }

    /**
     * Saring nilai mentah menjadi HEX kecil yang sah, atau string kosong. Nilai
     * ini masuk ke stylesheet, jadi apa pun di luar HEX ditolak — bukan sekadar
     * dirapikan.
     */
    public static function sanitize(mixed $color): string
    {
        $color = is_string($color) ? strtolower(trim($color)) : '';

        return preg_match('/^#(?:[0-9a-f]{3}|[0-9a-f]{6}|[0-9a-f]{8})$/', $color) === 1
            ? $color
            : '';
    }

    /**
     * CSS siap tempel: nilai `--nav-highlight` dan turunannya.
     *
     * - `soft` — latar sorotan di bar putih;
     * - `bright` — teks sorotan di atas hero gelap, dicerahkan agar kontras;
     * - `veil` — latar sorotan di atas hero gelap, tipis dan terang;
     * - `accent` — aksen di menu mobile yang berlatar gelap (nomor, garis, sub-menu).
     */
    public static function css(): string
    {
        $base = self::color() ?? 'var(--primary)';

        return implode("\n", [
            ':root {',
            '    --nav-highlight: '.$base.';',
            '    --nav-highlight-soft: color-mix(in oklab, var(--nav-highlight) 8%, white);',
            '    --nav-highlight-bright: color-mix(in oklab, var(--nav-highlight) 28%, white);',
            '    --nav-highlight-veil: color-mix(in oklab, var(--nav-highlight-bright) 16%, transparent);',
            '    --nav-highlight-accent: color-mix(in oklab, var(--nav-highlight) 55%, white);',
            '}',
        ]);
    }
}
