<?php

namespace App\Support;

use App\Models\ContentSection;

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
        'crosses' => [
            'svg' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><path d="M12 8v8M8 12h8" fill="none" stroke="#000" stroke-width="1.5" stroke-linecap="round"/></svg>',
            'width' => 24,
            'height' => 24,
        ],
        'circles' => [
            'svg' => '<svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 32 32"><circle cx="16" cy="16" r="9" fill="none" stroke="#000" stroke-width="1.2"/></svg>',
            'width' => 32,
            'height' => 32,
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
        'weave' => [
            'svg' => '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 16 16"><path d="M-4 20 20-4M-4 4 4-4M12 20l8-8M-4-4 20 20M-4 12l8 8M12-4l8 8" fill="none" stroke="#000" stroke-width="1"/></svg>',
            'width' => 16,
            'height' => 16,
        ],
        'diamonds' => [
            'svg' => '<svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 32 32"><path d="M16 3 29 16 16 29 3 16Z" fill="none" stroke="#000" stroke-width="1.2"/></svg>',
            'width' => 32,
            'height' => 32,
        ],
        'triangles' => [
            'svg' => '<svg xmlns="http://www.w3.org/2000/svg" width="30" height="26" viewBox="0 0 30 26"><path d="M15 3 27 23H3Z" fill="none" stroke="#000" stroke-width="1.2" stroke-linejoin="round"/></svg>',
            'width' => 30,
            'height' => 26,
        ],
        'zigzag' => [
            'svg' => '<svg xmlns="http://www.w3.org/2000/svg" width="40" height="20" viewBox="0 0 40 20"><path d="M0 15 10 5l10 10L30 5l10 10" fill="none" stroke="#000" stroke-width="1.5" stroke-linejoin="round"/></svg>',
            'width' => 40,
            'height' => 20,
        ],
        'scales' => [
            'svg' => '<svg xmlns="http://www.w3.org/2000/svg" width="40" height="20" viewBox="0 0 40 20"><path d="M0 20a20 20 0 0 1 40 0" fill="none" stroke="#000" stroke-width="1.3"/></svg>',
            'width' => 40,
            'height' => 20,
        ],
        'khatam' => [
            'svg' => '<svg xmlns="http://www.w3.org/2000/svg" width="44" height="44" viewBox="0 0 44 44"><path d="M22 4 40 22 22 40 4 22Z" fill="none" stroke="#000" stroke-width="1.1"/><path d="M9 9h26v26H9Z" fill="none" stroke="#000" stroke-width="1.1"/></svg>',
            'width' => 44,
            'height' => 44,
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

    /**
     * Sisi kotak pratinjau di panel, dan sisi terpanjang satu ubin di dalamnya
     * (piksel). Ubin dikecilkan sampai muat beberapa kali dalam kotak, sebab
     * pola seperti Garis Kotak hanya terbaca setelah berulang — satu ubinnya
     * sendiri cuma potongan sudut.
     */
    private const SWATCH_BOX = 32;

    private const SWATCH_TILE = 15;

    public static function exists(?string $pattern): bool
    {
        return $pattern !== null && array_key_exists($pattern, self::TILES);
    }

    /**
     * Pilihan pola untuk `Select` di panel: label didahului kotak pratinjau
     * berisi polanya sendiri. Dipakai dengan `->allowHtml()->native(false)`.
     *
     * Kotaknya memakai ubin yang sama persis dengan yang dilukis di halaman
     * depan, jadi pratinjaunya tidak mungkin berbeda dari hasil aslinya.
     *
     * @return array<string, string>
     */
    public static function selectOptions(): array
    {
        $options = [];

        foreach (ContentSection::BACKGROUND_PATTERNS as $key => $label) {
            $options[$key] = self::exists($key)
                ? self::option(self::tileFill($key), $label)
                : self::option('', $label);
        }

        return $options;
    }

    /**
     * Isi kotak pratinjau: ubin polanya, diwarnai mengikuti warna teks panel
     * agar tetap terbaca di mode terang maupun gelap.
     */
    private static function tileFill(string $pattern): string
    {
        $scale = self::SWATCH_TILE / max(self::width($pattern), self::height($pattern));
        $maskWidth = max(2, (int) round(self::width($pattern) * $scale));
        $maskHeight = max(2, (int) round(self::height($pattern) * $scale));
        $url = self::maskUrl($pattern);

        return 'background-color:currentColor;opacity:.75;'
            ."-webkit-mask-image:{$url};mask-image:{$url};"
            ."-webkit-mask-size:{$maskWidth}px {$maskHeight}px;mask-size:{$maskWidth}px {$maskHeight}px;"
            .'-webkit-mask-repeat:repeat;mask-repeat:repeat;';
    }

    /**
     * Satu baris pilihan: kotak pratinjau di kiri, nama pola di kanan. Kotak
     * dibiarkan kosong untuk pilihan "Tanpa Pola" supaya labelnya tetap sejajar.
     */
    private static function option(string $tileFill, string $label): string
    {
        $box = self::SWATCH_BOX;

        return '<span style="display:inline-flex;align-items:center;gap:.5rem">'
            ."<span style=\"flex:none;width:{$box}px;height:{$box}px;border-radius:.375rem;"
            .'border:1px solid color-mix(in oklab, currentColor 25%, transparent);'
            .'overflow:hidden;position:relative">'
            .($tileFill === '' ? '' : '<span style="position:absolute;inset:0;'.$tileFill.'"></span>')
            .'</span>'
            .'<span>'.e($label).'</span>'
            .'</span>';
    }

    /**
     * Ubin pola sebagai `data:` URI, siap dipasang pada properti mask CSS.
     */
    public static function maskUrl(string $pattern): ?string
    {
        if (! self::exists($pattern)) {
            return null;
        }

        // Tanpa tanda kutip: isinya sudah ter-percent-encode seluruhnya, jadi tak
        // ada spasi atau kurung yang perlu dilindungi — sementara kutip ganda
        // justru menutup atribut style="..." lebih awal saat URL ini ditanam
        // langsung ke HTML (mis. pratinjau pada pilihan Select di panel).
        return 'url(data:image/svg+xml,'.rawurlencode(self::TILES[$pattern]['svg']).')';
    }

    /**
     * Lebar satu ubin (piksel) sebelum diskalakan. `SectionBackground` yang
     * menerapkan skala pilihan admin, agar ukuran mask dan jarak tempuh
     * animasi selalu berasal dari satu angka yang sama.
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
