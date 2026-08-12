<?php

namespace App\Support;

/**
 * Pola SVG penghias latar seksi.
 *
 * Tiap pola adalah satu ubin kecil yang diulang menutupi seksi. SVG-nya dipakai
 * sebagai *mask*, bukan gambar latar: yang terlihat adalah warna aksen tema di
 * balik ubin, sehingga polanya ikut berubah begitu admin mengganti warna utama
 * situs — dan tidak perlu satu berkas gambar pun di storage.
 */
class SectionPatterns
{
    /**
     * Ubin tiap pola: gambar SVG beserta lebar & tinggi pengulangannya (piksel).
     * Ukuran ubin ini juga jadi jarak tempuh animasi hanyut — bergeser tepat
     * satu ubin membuat pengulangannya kembali segaris, sehingga perputarannya
     * tidak terlihat berkedut.
     *
     * @var array<string, array{svg: string, width: int, height: int}>
     */
    private const TILES = [
        'dots' => [
            'svg' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><circle cx="12" cy="12" r="1.6" fill="#000"/></svg>',
            'width' => 24,
            'height' => 24,
        ],
        'grid' => [
            'svg' => '<svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 32 32"><path d="M32 0H0v32" fill="none" stroke="#000" stroke-width="1"/></svg>',
            'width' => 32,
            'height' => 32,
        ],
        'diagonal' => [
            'svg' => '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 16 16"><path d="M-4 20 20-4M-4 4 4-4M12 20l8-8" fill="none" stroke="#000" stroke-width="1.5"/></svg>',
            'width' => 16,
            'height' => 16,
        ],
        'waves' => [
            'svg' => '<svg xmlns="http://www.w3.org/2000/svg" width="60" height="20" viewBox="0 0 60 20"><path d="M0 14q7.5-10 15 0t15 0 15 0 15 0" fill="none" stroke="#000" stroke-width="1.5"/></svg>',
            'width' => 60,
            'height' => 20,
        ],
        'arabesque' => [
            'svg' => '<svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 40 40"><path d="M20 2 25 15 38 20 25 25 20 38 15 25 2 20 15 15Z" fill="none" stroke="#000" stroke-width="1.2"/><circle cx="20" cy="20" r="2" fill="#000"/></svg>',
            'width' => 40,
            'height' => 40,
        ],
    ];

    public static function exists(?string $pattern): bool
    {
        return $pattern !== null && array_key_exists($pattern, self::TILES);
    }

    /**
     * Ubin pola sebagai `data:` URI, siap dipasang pada properti mask CSS.
     */
    public static function maskUrl(string $pattern): ?string
    {
        if (! self::exists($pattern)) {
            return null;
        }

        return 'url("data:image/svg+xml,'.rawurlencode(self::TILES[$pattern]['svg']).'")';
    }

    /**
     * Jarak pengulangan ubin, mis. `24px 24px`.
     */
    public static function size(string $pattern): ?string
    {
        return self::exists($pattern)
            ? self::TILES[$pattern]['width'].'px '.self::TILES[$pattern]['height'].'px'
            : null;
    }

    /**
     * Lebar satu ubin (piksel) — jarak tempuh satu putaran animasi mendatar.
     */
    public static function width(string $pattern): int
    {
        return self::exists($pattern) ? self::TILES[$pattern]['width'] : 0;
    }

    /**
     * Tinggi satu ubin (piksel) — jarak tempuh satu putaran animasi menurun.
     */
    public static function height(string $pattern): int
    {
        return self::exists($pattern) ? self::TILES[$pattern]['height'] : 0;
    }
}
