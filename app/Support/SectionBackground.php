<?php

namespace App\Support;

use App\Models\ContentSection;

/**
 * Pengaturan latar satu seksi halaman depan.
 *
 * Bentuk data ini dipakai bersama oleh seksi bawaan — nilainya disimpan sebagai
 * setting `section_{key}_background*` lewat Pengaturan Halaman Depan — dan seksi
 * dinamis yang menyimpannya sebagai kolom tabel, sehingga komponen Blade
 * `<x-section-background>` hanya perlu mengenal satu sumber data.
 */
class SectionBackground
{
    /**
     * Jenis latar seksi bawaan. `default` membiarkan seksi memakai latar
     * rancangannya sendiri (mis. gradasi gelap pada seksi Kontak).
     *
     * @var array<string, string>
     */
    public const BACKGROUNDS = [
        'default' => 'Bawaan Seksi',
        'base' => 'Abu Lembut',
        'alt' => 'Putih Bersih',
        'image' => 'Gambar',
    ];

    public function __construct(
        public readonly string $mode = 'default',
        public readonly ?string $image = null,
        public readonly int $blur = 0,
        public readonly int $overlay = 0,
        public readonly string $parallaxMode = 'none',
        public readonly int $parallaxSpeed = 30,
        public readonly bool $lightText = false,
    ) {}

    /**
     * Latar seksi bawaan, dibaca dari setting halaman depan.
     *
     * @param  string  $sectionKey  kunci seksi pada `section_order`, mis. `section_stats`
     */
    public static function forSection(string $sectionKey): self
    {
        return new self(
            mode: (string) (setting("{$sectionKey}_background") ?: 'default'),
            image: setting("{$sectionKey}_background_image") ?: null,
            blur: (int) setting("{$sectionKey}_background_blur", 0),
            overlay: (int) setting("{$sectionKey}_background_overlay", 0),
            parallaxMode: (string) (setting("{$sectionKey}_background_parallax_mode") ?: 'none'),
            parallaxSpeed: (int) setting("{$sectionKey}_background_parallax_speed", 30),
            lightText: setting_bool("{$sectionKey}_background_light_text", true),
        );
    }

    /**
     * Latar seksi dinamis, dibaca dari kolom recordnya.
     */
    public static function forContentSection(ContentSection $section): self
    {
        return new self(
            mode: match ($section->background) {
                'image' => 'image',
                'alt' => 'alt',
                default => 'base',
            },
            image: $section->background_image,
            blur: (int) $section->background_blur,
            overlay: (int) $section->background_overlay,
            parallaxMode: (string) ($section->background_parallax_mode ?: 'none'),
            parallaxSpeed: (int) $section->background_parallax_speed,
            lightText: (bool) $section->background_light_text,
        );
    }

    /**
     * Latar gambar hanya aktif bila modenya dipilih dan gambarnya terisi.
     */
    public function hasImage(): bool
    {
        return $this->mode === 'image' && filled($this->image);
    }

    public function imageUrl(): ?string
    {
        return $this->hasImage() ? icon_url($this->image) : null;
    }

    /**
     * Kepekatan lapisan gelap sebagai nilai opacity CSS (0–1).
     */
    public function overlayOpacity(): float
    {
        return round(min(100, max(0, $this->overlay)) / 100, 2);
    }

    /**
     * Radius blur yang benar-benar dipakai (0 bila seksi tanpa gambar latar).
     */
    public function blurRadius(): int
    {
        return $this->hasImage() ? max(0, $this->blur) : 0;
    }

    /**
     * Intensitas parallax sebagai pengali pergeseran (0–1).
     */
    public function parallaxFactor(): float
    {
        return round(min(ContentSection::PARALLAX_MAX_SPEED, max(0, $this->parallaxSpeed)) / 100, 2);
    }

    public function usesScrollParallax(): bool
    {
        return $this->hasImage() && $this->parallaxMode === 'scroll';
    }

    /**
     * Latar terkunci ke layar; kontennya yang meluncur di atasnya.
     */
    public function usesFixedBackground(): bool
    {
        return $this->hasImage() && $this->parallaxMode === 'fixed';
    }

    /**
     * Teks putih dipakai hanya di atas latar gambar.
     */
    public function usesLightText(): bool
    {
        return $this->hasImage() && $this->lightText;
    }

    /**
     * Blur & parallax dikompensasi dengan scale agar tepi gambar tidak bocor.
     * Pada parallax, separuh sisa ruang hasil scale itulah jarak geser
     * maksimalnya, sehingga gambar tetap menutup seksi di posisi gulir mana pun.
     * Latar terkunci tidak perlu diperbesar karena ia memang tidak bergerak.
     */
    public function imageScale(): float
    {
        return match (true) {
            $this->usesScrollParallax() => round(1 + $this->parallaxFactor(), 3),
            $this->blurRadius() > 0 => 1.1,
            default => 1,
        };
    }

    public function parallaxAmplitude(): float
    {
        return round($this->parallaxFactor() / 2, 3);
    }

    /**
     * Nilai CSS untuk properti `background` pada elemen seksi. Saat memakai
     * gambar, seksinya dibiarkan transparan: warna dasar dipegang lapisan latar
     * yang duduk di z-index negatif, di bawah latar elemen seksi itu sendiri.
     *
     * @param  string|null  $default  latar rancangan seksi itu sendiri, dipakai
     *                                saat admin memilih "Bawaan Seksi"
     */
    public function style(?string $default = null): string
    {
        return match (true) {
            $this->hasImage() => 'transparent',
            $this->mode === 'base' => 'var(--bg)',
            $this->mode === 'alt' => 'var(--bg-alt, var(--bg))',
            default => $default ?: 'var(--bg)',
        };
    }

    /**
     * Warna lapisan gelap di atas gambar, siap dipakai sebagai nilai CSS.
     */
    public function overlayColor(): string
    {
        return 'rgba(17,24,39,'.$this->overlayOpacity().')';
    }
}
