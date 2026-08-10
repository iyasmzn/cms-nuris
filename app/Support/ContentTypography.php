<?php

namespace App\Support;

/**
 * Ukuran huruf teks konten (artikel, halaman, program, blok "Konten Tambahan").
 *
 * Rancangan aslinya memakai 18px pada layar lebar — lebih besar daripada teks
 * umum website (16px) — sehingga admin bisa menyetelnya lewat satu pengaturan
 * global. Nilai yang disimpan adalah ukuran teks konten di layar lebar dalam
 * piksel; yang dipakai CSS adalah pengalinya (`--content-scale`) supaya langkah
 * responsif tiap elemen tetap dipegang stylesheet-nya sendiri.
 */
class ContentTypography
{
    /**
     * Ukuran teks konten yang bisa dipilih (piksel pada layar lebar) → labelnya
     * di panel.
     *
     * @var array<int, string>
     */
    public const SIZES = [
        15 => 'Kecil — 15px',
        16 => 'Normal — 16px (setara teks umum website)',
        17 => 'Sedang — 17px',
        18 => 'Besar — 18px (bawaan)',
        20 => 'Sangat Besar — 20px',
    ];

    /**
     * Ukuran bawaan, yaitu ukuran yang dipakai rancangan sebelum pengaturan ini
     * ada. Jadi pemasangan lama tampil persis seperti sebelumnya.
     */
    public const DEFAULT_SIZE = 18;

    public const SETTING_KEY = 'content_font_size';

    /**
     * Ukuran tersimpan, dikembalikan ke bawaan bila nilainya bukan salah satu
     * pilihan yang sah — supaya nilai aneh dari basis data tidak bocor ke CSS.
     */
    public static function size(): int
    {
        return self::sanitizeSize(setting(self::SETTING_KEY, self::DEFAULT_SIZE));
    }

    /**
     * Saring nilai mentah (mis. dari form) menjadi salah satu ukuran yang sah.
     */
    public static function sanitizeSize(mixed $size): int
    {
        $size = (int) $size;

        return array_key_exists($size, self::SIZES) ? $size : self::DEFAULT_SIZE;
    }

    /**
     * Pengali ukuran huruf terhadap rancangan bawaan, untuk custom property
     * `--content-scale`.
     */
    public static function scale(?int $size = null): float
    {
        $size = $size !== null && array_key_exists($size, self::SIZES)
            ? $size
            : self::size();

        return round($size / self::DEFAULT_SIZE, 4);
    }
}
