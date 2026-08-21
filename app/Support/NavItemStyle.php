<?php

namespace App\Support;

/**
 * Tampilan per item menu navigasi.
 *
 * Selain warna sorotan yang berlaku menyeluruh di [[NavHighlight]], tiap item
 * boleh punya tampilannya sendiri: warna teks, warna latar, dan gaya — apakah ia
 * tautan biasa atau dipromosikan menjadi tombol ajakan seperti "Daftar Sekarang".
 *
 * Warnanya diteruskan sebagai custom property pada elemen item (`--item-text`,
 * `--item-bg`, `--item-bg-hover`), bukan sebagai `color`/`background` langsung.
 * Dengan begitu satu aturan CSS bisa melayani dua hal sekaligus: item yang punya
 * warna sendiri memakai warnanya, item yang tidak jatuh ke palet bersama lewat
 * nilai cadangan `var()`. `--item-bg-hover` disiapkan di sini — bukan di CSS —
 * sebab CSS tidak bisa menanyakan apakah sebuah custom property terisi, sehingga
 * tanpa itu item berlatar warna sendiri akan tampak mati saat disentuh kursor.
 */
class NavItemStyle
{
    /**
     * Gaya yang tersedia untuk item menu tingkat atas.
     *
     * @var array<string, string>
     */
    public const STYLES = [
        'link' => 'Tautan Biasa',
        'button' => 'Tombol Isi',
        'outline' => 'Tombol Garis',
    ];

    public const DEFAULT_STYLE = 'link';

    /**
     * Kunci warna yang dikenali pada satu item menu.
     *
     * @var array<int, string>
     */
    public const COLOR_KEYS = ['text_color', 'bg_color'];

    /**
     * Pilihan untuk dropdown di panel.
     *
     * @return array<string, string>
     */
    public static function options(): array
    {
        return self::STYLES;
    }

    /**
     * Saring nilai mentah menjadi salah satu gaya yang sah.
     */
    public static function sanitize(mixed $style): string
    {
        $style = is_string($style) ? $style : '';

        return array_key_exists($style, self::STYLES) ? $style : self::DEFAULT_STYLE;
    }

    /**
     * Kelas CSS untuk gaya pilihan admin. Tautan biasa tidak memerlukan kelas
     * tambahan — itulah tampilan bawaan menu.
     */
    public static function cssClass(mixed $style): string
    {
        return match (self::sanitize($style)) {
            'button' => 'nav-item-button',
            'outline' => 'nav-item-outline',
            default => '',
        };
    }

    /**
     * Isi atribut `style` untuk satu item: custom property warnanya saja, atau
     * string kosong bila item ini tidak punya warna sendiri.
     *
     * @param  array<string, mixed>  $item
     */
    public static function cssVars(array $item): string
    {
        $declarations = [];

        if ($text = HexColor::sanitize($item['text_color'] ?? null)) {
            $declarations[] = '--item-text:'.$text;
        }

        if ($background = HexColor::sanitize($item['bg_color'] ?? null)) {
            $declarations[] = '--item-bg:'.$background;
            $declarations[] = '--item-bg-hover:color-mix(in oklab, '.$background.' 88%, black)';
        }

        return implode(';', $declarations);
    }

    /**
     * Bersihkan gaya dan warna pada daftar item menu sebelum disimpan, termasuk
     * sub-menunya. Kunci lain — label, url, target — dibiarkan apa adanya.
     *
     * @param  array<int, array<string, mixed>>  $items
     * @return array<int, array<string, mixed>>
     */
    public static function sanitizeItems(array $items): array
    {
        return array_map(function (array $item): array {
            $item['style'] = self::sanitize($item['style'] ?? null);

            foreach (self::COLOR_KEYS as $key) {
                $item[$key] = HexColor::sanitize($item[$key] ?? null);
            }

            if (is_array($item['children'] ?? null)) {
                $item['children'] = array_map(function (array $child): array {
                    foreach (self::COLOR_KEYS as $key) {
                        $child[$key] = HexColor::sanitize($child[$key] ?? null);
                    }

                    return $child;
                }, $item['children']);
            }

            return $item;
        }, $items);
    }
}
