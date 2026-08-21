<?php

namespace App\Support;

/**
 * Penyaring kode warna HEX untuk nilai yang berakhir di dalam stylesheet.
 *
 * Warna pilihan admin — sorotan menu, warna per item menu — ditempelkan langsung
 * ke CSS. Karena itu nilainya tidak sekadar dirapikan melainkan ditolak bila
 * bukan HEX, supaya tidak ada yang bisa menyelipkan deklarasi CSS lain lewat
 * kolom warna.
 */
class HexColor
{
    /**
     * HEX kecil yang sah (`#abc`, `#aabbcc`, `#aabbccdd`), atau string kosong.
     */
    public static function sanitize(mixed $color): string
    {
        $color = is_string($color) ? strtolower(trim($color)) : '';

        return preg_match('/^#(?:[0-9a-f]{3}|[0-9a-f]{6}|[0-9a-f]{8})$/', $color) === 1
            ? $color
            : '';
    }

    /**
     * Aturan validasi untuk kolom warna di panel — pasangan dari penyaring di
     * atas, supaya admin melihat pesan kesalahan alih-alih warnanya diam-diam
     * hilang saat disimpan.
     */
    public static function validationRule(): string
    {
        return 'regex:/^#(?:[0-9a-fA-F]{3}|[0-9a-fA-F]{6}|[0-9a-fA-F]{8})$/';
    }
}
