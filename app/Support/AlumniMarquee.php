<?php

namespace App\Support;

/**
 * Perilaku & tampilan baris logo kampus pada seksi Jejak Alumni.
 *
 * Nilainya disimpan sebagai setting `alumni_marquee_*` lewat Pengaturan Halaman
 * Depan dan dibaca partial `sections/alumni.blade.php`: sebagian menjadi state
 * Alpine (jalan otomatis, arah, kecepatan) dan sisanya menjadi custom property
 * CSS (ukuran kartu, tinggi logo, jarak antar-kartu).
 */
class AlumniMarquee
{
    /**
     * Arah jalannya baris logo.
     *
     * @var array<string, string>
     */
    public const DIRECTIONS = [
        'left' => 'Ke Kiri',
        'right' => 'Ke Kanan',
    ];

    /** Kecepatan dalam piksel per detik — bebas frame rate. */
    public const MIN_SPEED = 5;

    public const MAX_SPEED = 150;

    public const DEFAULT_SPEED = 18;

    /** Tinggi gambar logo, dalam piksel. */
    public const MIN_LOGO_HEIGHT = 32;

    public const MAX_LOGO_HEIGHT = 160;

    public const DEFAULT_LOGO_HEIGHT = 72;

    /** Lebar satu kartu logo, dalam piksel. */
    public const MIN_CARD_WIDTH = 120;

    public const MAX_CARD_WIDTH = 360;

    public const DEFAULT_CARD_WIDTH = 216;

    /** Jarak antar kartu, dalam piksel. */
    public const MIN_GAP = 0;

    public const MAX_GAP = 80;

    public const DEFAULT_GAP = 20;

    public function __construct(
        public readonly bool $autoplay = true,
        public readonly bool $pauseOnHover = true,
        public readonly int $speed = self::DEFAULT_SPEED,
        public readonly string $direction = 'left',
        public readonly int $logoHeight = self::DEFAULT_LOGO_HEIGHT,
        public readonly int $cardWidth = self::DEFAULT_CARD_WIDTH,
        public readonly int $gap = self::DEFAULT_GAP,
        public readonly bool $grayscale = true,
        public readonly bool $showName = true,
    ) {}

    public static function fromSettings(): self
    {
        $direction = (string) (setting('alumni_marquee_direction') ?: 'left');

        return new self(
            autoplay: setting_bool('alumni_marquee_autoplay', true),
            pauseOnHover: setting_bool('alumni_marquee_pause_on_hover', true),
            speed: self::clamp((int) setting('alumni_marquee_speed', self::DEFAULT_SPEED), self::MIN_SPEED, self::MAX_SPEED),
            direction: isset(self::DIRECTIONS[$direction]) ? $direction : 'left',
            logoHeight: self::clamp((int) setting('alumni_marquee_logo_height', self::DEFAULT_LOGO_HEIGHT), self::MIN_LOGO_HEIGHT, self::MAX_LOGO_HEIGHT),
            cardWidth: self::clamp((int) setting('alumni_marquee_card_width', self::DEFAULT_CARD_WIDTH), self::MIN_CARD_WIDTH, self::MAX_CARD_WIDTH),
            gap: self::clamp((int) setting('alumni_marquee_gap', self::DEFAULT_GAP), self::MIN_GAP, self::MAX_GAP),
            grayscale: setting_bool('alumni_marquee_grayscale', true),
            showName: setting_bool('alumni_marquee_show_name', true),
        );
    }

    /**
     * Kecepatan bertanda: negatif berarti baris berjalan ke kanan, sebab yang
     * digeser adalah `scrollLeft`.
     */
    public function signedSpeed(): int
    {
        return $this->direction === 'right' ? -$this->speed : $this->speed;
    }

    /**
     * Ukuran kartu diteruskan ke CSS sebagai custom property agar aturan gaya
     * di partial tidak perlu ditulis ulang per nilai.
     */
    public function styleAttribute(): string
    {
        return implode(';', [
            '--alumni-logo-card-width: '.$this->cardWidth.'px',
            '--alumni-logo-height: '.$this->logoHeight.'px',
            '--alumni-logo-gap: '.$this->gap.'px',
        ]);
    }

    private static function clamp(int $value, int $min, int $max): int
    {
        return max($min, min($max, $value));
    }
}
