<?php

namespace App\Support;

/**
 * Perilaku slider hero di halaman depan.
 *
 * Nilainya disimpan sebagai setting `hero_*` lewat Pengaturan Halaman Depan dan
 * dibaca bersama oleh `welcome.blade.php` (state Alpine slider) serta partial
 * `sections/hero.blade.php` (kelas & durasi animasinya).
 */
class HeroSlider
{
    /**
     * Gaya perpindahan antar slide. Kuncinya dipakai apa adanya sebagai kelas
     * CSS `hero-anim-{key}` pada partial hero.
     *
     * @var array<string, string>
     */
    public const TRANSITIONS = [
        'fade' => 'Memudar',
        'slide' => 'Geser Samping',
        'slide-vertical' => 'Geser Atas-Bawah',
        'zoom' => 'Zoom Halus',
    ];

    public const MIN_INTERVAL = 2;

    public const MAX_INTERVAL = 20;

    public const DEFAULT_INTERVAL = 5;

    public const MIN_DURATION = 200;

    public const MAX_DURATION = 2000;

    public const DEFAULT_DURATION = 700;

    public function __construct(
        public readonly bool $autoplay = true,
        public readonly int $interval = self::DEFAULT_INTERVAL,
        public readonly bool $pauseOnHover = true,
        public readonly string $transition = 'fade',
        public readonly int $duration = self::DEFAULT_DURATION,
    ) {}

    public static function fromSettings(): self
    {
        $transition = (string) (setting('hero_transition') ?: 'fade');

        return new self(
            autoplay: setting_bool('hero_autoplay', true),
            interval: self::clamp((int) setting('hero_interval', self::DEFAULT_INTERVAL), self::MIN_INTERVAL, self::MAX_INTERVAL),
            pauseOnHover: setting_bool('hero_pause_on_hover', true),
            transition: isset(self::TRANSITIONS[$transition]) ? $transition : 'fade',
            duration: self::clamp((int) setting('hero_transition_duration', self::DEFAULT_DURATION), self::MIN_DURATION, self::MAX_DURATION),
        );
    }

    /**
     * Jeda antar slide dalam milidetik, siap dipakai `setInterval`.
     */
    public function intervalMs(): int
    {
        return $this->interval * 1000;
    }

    private static function clamp(int $value, int $min, int $max): int
    {
        return max($min, min($max, $value));
    }
}
