<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\ContentSection;

/**
 * Pola penghias pita kepala halaman — hero subhalaman, kepala halaman statis,
 * sampai layar masuk/daftar.
 *
 * Berbeda dari pola latar seksi yang disetel satu per satu, pola ini disetel
 * sekali di Tema & Tampilan dan berlaku untuk seluruh pita sekaligus: pita
 * kepala adalah satu elemen rancangan yang sama di semua halaman, dan
 * membiarkannya berbeda-beda per halaman justru membuat situs terasa tidak
 * satu bangunan.
 *
 * Ubinnya diambil dari daftar pola latar seksi (`SectionPatterns`). Bawaannya
 * bintang delapan (Khatam) putih tipis — penerus ornamen geometris Islami yang
 * dulu terpasang tetap di setiap hero.
 */
class HeroPattern
{
    /**
     * Warna pola di atas pita. Pilihannya sengaja hanya yang terbaca di atas
     * latar gelap — token teks gelap seperti `--text` akan lenyap di sana.
     *
     * @var array<string, string>
     */
    public const COLORS = [
        'white' => 'Putih',
        'gold' => 'Emas',
        'primary_light' => 'Utama Terang',
        'custom' => 'Warna Sendiri',
    ];

    public const DEFAULT_PATTERN = 'khatam';

    public const DEFAULT_COLOR = 'white';

    public const DEFAULT_OPACITY = 8;

    /**
     * Ubin Khatam 44px pada skala 150% (66px) mendekati ubin 60px ornamen lama.
     */
    public const DEFAULT_SCALE = 150;

    public function __construct(
        public readonly string $pattern = self::DEFAULT_PATTERN,
        public readonly int $opacity = self::DEFAULT_OPACITY,
        public readonly int $scale = self::DEFAULT_SCALE,
        public readonly string $color = self::DEFAULT_COLOR,
        public readonly ?string $customColor = null,
    ) {}

    /**
     * Pilihan "Tanpa Pola" tersimpan sebagai `none`, jadi hanya setting yang
     * belum pernah disimpan yang jatuh ke pola bawaan.
     */
    public static function fromSettings(): self
    {
        return new self(
            pattern: (string) (setting('hero_pattern') ?: self::DEFAULT_PATTERN),
            opacity: (int) setting('hero_pattern_opacity', self::DEFAULT_OPACITY),
            scale: (int) setting('hero_pattern_scale', self::DEFAULT_SCALE),
            color: (string) (setting('hero_pattern_color') ?: self::DEFAULT_COLOR),
            customColor: setting('hero_pattern_custom_color') ?: null,
        );
    }

    /**
     * Pola hanya dilukis bila ubinnya benar-benar ada — "Tanpa Pola" atau kunci
     * yang tidak dikenal membuat pita kembali polos, bukan menggagalkan render.
     */
    public function isActive(): bool
    {
        return SectionPatterns::exists($this->pattern);
    }

    public function maskUrl(): ?string
    {
        return $this->isActive() ? SectionPatterns::maskUrl($this->pattern) : null;
    }

    public function tileSize(): ?string
    {
        return $this->isActive() ? $this->tileWidth().'px '.$this->tileHeight().'px' : null;
    }

    public function tileWidth(): int
    {
        return $this->scaled(SectionPatterns::width($this->pattern));
    }

    public function tileHeight(): int
    {
        return $this->scaled(SectionPatterns::height($this->pattern));
    }

    private function scaled(int $size): int
    {
        $scale = array_key_exists($this->scale, ContentSection::PATTERN_SCALES)
            ? $this->scale
            : self::DEFAULT_SCALE;

        return max(1, (int) round($size * $scale / 100));
    }

    public function opacityValue(): float
    {
        return round(min(100, max(0, $this->opacity)) / 100, 2);
    }

    /**
     * Warna pola sebagai nilai CSS. "Utama Terang" diteruskan sebagai `var(...)`
     * agar ikut berubah saat warna tema diganti; "Emas" sama dengan aksen amber
     * pada label hero; hex kiriman admin disaring ketat karena nilainya ditanam
     * mentah ke dalam CSS.
     */
    public function colorCss(): string
    {
        return match ($this->color) {
            'gold' => '#fbbf24',
            'primary_light' => 'var(--primary-300)',
            'custom' => $this->safeCustomColor() ?? '#ffffff',
            default => '#ffffff',
        };
    }

    private function safeCustomColor(): ?string
    {
        $color = trim((string) $this->customColor);

        return preg_match('/^#(?:[0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/', $color) === 1
            ? $color
            : null;
    }
}
