<?php

namespace App\Support;

/**
 * Warna & animasi judul (`h1`) pada slide hero halaman depan.
 *
 * Nilainya milik tiap slide — kolom JSON `slides.title_effect` yang diisi lewat
 * form Slide — jadi satu hero bisa mencampur beberapa gaya: slide pembuka
 * diketik huruf demi huruf, slide berikutnya cukup disorot stabilo. Yang dikirim
 * ke Blade hanyalah satu kelas efek dan beberapa custom property; keyframes-nya
 * sendiri hidup di partial `partials/hero-title-effects.blade.php` supaya panel
 * dan halaman depan memakai stylesheet yang sama persis.
 *
 * Semua efek — kecuali mesin ketik yang butuh JavaScript — murni CSS dan
 * dipicu oleh kelas `is-active` milik slide, sehingga animasinya terulang
 * setiap kali slide itu kembali tampil.
 */
class HeroTitleEffect
{
    /**
     * Gaya animasi judul. Kuncinya dipakai apa adanya sebagai kelas CSS
     * `hero-fx-{key}` pada judul.
     *
     * @var array<string, string>
     */
    public const EFFECTS = [
        'none' => 'Tanpa Efek',
        'fade-up' => 'Muncul Naik',
        'wipe' => 'Tersapu Muncul',
        'typing' => 'Mesin Ketik',
        'underline' => 'Garis Bawah',
        'highlight' => 'Stabilo',
        'border' => 'Bingkai',
        'gradient' => 'Gradasi Berjalan',
        'shine' => 'Kilau Melintas',
        'glow' => 'Cahaya Berdenyut',
    ];

    /**
     * Efek yang benar-benar memakai warna aksen — sebagai garis, sapuan, kilau,
     * atau kursor mesin ketik. Sisanya hanya menggerakkan teksnya sendiri.
     *
     * @var array<int, string>
     */
    public const ACCENT_EFFECTS = [
        'typing',
        'underline',
        'highlight',
        'border',
        'gradient',
        'shine',
        'glow',
    ];

    public const DEFAULT_EFFECT = 'none';

    /** Lama satu putaran animasi, dalam milidetik. */
    public const MIN_DURATION = 200;

    public const MAX_DURATION = 6000;

    public const DEFAULT_DURATION = 1600;

    /** Jeda sebelum animasi mulai, dalam milidetik. */
    public const MIN_DELAY = 0;

    public const MAX_DELAY = 3000;

    public const DEFAULT_DELAY = 0;

    /** Batas waktu ketik per huruf agar judul pendek tak terasa lamban. */
    private const MIN_TYPING_SPEED = 20;

    private const MAX_TYPING_SPEED = 400;

    public function __construct(
        public readonly string $effect = self::DEFAULT_EFFECT,
        public readonly string $color = '',
        public readonly string $accentColor = '',
        public readonly int $duration = self::DEFAULT_DURATION,
        public readonly int $delay = self::DEFAULT_DELAY,
        public readonly bool $loop = true,
        public readonly bool $caret = true,
    ) {}

    /**
     * Bentuk satu efek dari nilai mentah apa pun — kolom JSON milik slide
     * maupun state form yang belum disimpan, sehingga pratinjau di panel
     * menyaring dan membatasi nilainya dengan aturan yang persis sama.
     *
     * @param  array<string, mixed>  $state
     */
    public static function fromArray(array $state): self
    {
        return new self(
            effect: self::sanitizeEffect($state['effect'] ?? null),
            color: HexColor::sanitize($state['color'] ?? null),
            accentColor: HexColor::sanitize($state['accent_color'] ?? null),
            duration: self::clamp((int) ($state['duration'] ?? self::DEFAULT_DURATION), self::MIN_DURATION, self::MAX_DURATION),
            delay: self::clamp((int) ($state['delay'] ?? self::DEFAULT_DELAY), self::MIN_DELAY, self::MAX_DELAY),
            loop: (bool) ($state['loop'] ?? true),
            caret: (bool) ($state['caret'] ?? true),
        );
    }

    /**
     * Saring nilai mentah menjadi salah satu efek yang dikenali.
     */
    public static function sanitizeEffect(mixed $effect): string
    {
        $effect = is_string($effect) ? $effect : '';

        return array_key_exists($effect, self::EFFECTS) ? $effect : self::DEFAULT_EFFECT;
    }

    /**
     * Efek ini memakai warna aksen, sehingga kolom warnanya perlu ditawarkan.
     */
    public static function usesAccent(mixed $effect): bool
    {
        return in_array(self::sanitizeEffect($effect), self::ACCENT_EFFECTS, true);
    }

    public function hasEffect(): bool
    {
        return $this->effect !== self::DEFAULT_EFFECT;
    }

    public function isTyping(): bool
    {
        return $this->effect === 'typing';
    }

    /**
     * Kursor kedip hanya berarti kalau judulnya memang sedang diketik.
     */
    public function showsCaret(): bool
    {
        return $this->isTyping() && $this->caret;
    }

    /**
     * Daftar kelas untuk elemen judul: kelas dasarnya, ditambah kelas efek bila
     * ada yang dipilih.
     */
    public function cssClass(): string
    {
        return $this->hasEffect() ? 'hero-title hero-fx-'.$this->effect : 'hero-title';
    }

    /**
     * Stylesheet efek baru perlu ikut dicetak kalau judulnya memang dirias —
     * entah digerakkan atau sekadar diberi warna sendiri.
     */
    public function needsStylesheet(): bool
    {
        return $this->hasEffect() || $this->color !== '';
    }

    /**
     * Isi atribut `style` judul: warna pilihan admin dan takaran animasinya.
     * Warna yang dikosongkan sengaja tidak ditulis supaya nilai cadangan
     * `var()` di stylesheet — putih bawaan hero dan warna utama tema — yang
     * dipakai.
     */
    public function cssVars(): string
    {
        $declarations = [];

        if ($this->color !== '') {
            $declarations[] = '--hero-title-color:'.$this->color;
        }

        if ($this->accentColor !== '') {
            $declarations[] = '--hero-title-accent:'.$this->accentColor;
        }

        if ($this->hasEffect()) {
            $declarations[] = '--hero-title-duration:'.$this->duration.'ms';
            $declarations[] = '--hero-title-delay:'.$this->delay.'ms';
            $declarations[] = '--hero-title-repeat:'.($this->loop ? 'infinite' : '1');
        }

        return implode(';', $declarations);
    }

    /**
     * Bentuk datar untuk mengisi form Slide. Slide yang dibuat sebelum efek
     * judul ada bernilai null; tanpa dinormalkan dulu, kolom animasinya
     * terbaca kosong dan slide lama jadi tak bisa disimpan sama sekali.
     *
     * @return array<string, mixed>
     */
    public function toFormState(): array
    {
        return [
            'color' => $this->color,
            'effect' => $this->effect,
            'accent_color' => $this->accentColor,
            'duration' => $this->duration,
            'delay' => $this->delay,
            'loop' => $this->loop,
            'caret' => $this->caret,
        ];
    }

    /**
     * Lama satu huruf muncul saat mengetik. Diturunkan dari lama animasi
     * dibagi panjang judul, jadi admin cukup mengatur satu angka: judul
     * panjang otomatis diketik lebih cepat agar selesai pada waktu yang sama.
     */
    public function typingSpeedFor(string $title): int
    {
        $length = max(1, mb_strlen(trim($title)));

        return self::clamp((int) round($this->duration / $length), self::MIN_TYPING_SPEED, self::MAX_TYPING_SPEED);
    }

    /**
     * Jeda saat judul sudah utuh, sebelum dihapus dan diketik ulang.
     */
    public function typingHoldMs(): int
    {
        return max(800, $this->duration);
    }

    private static function clamp(int $value, int $min, int $max): int
    {
        return max($min, min($max, $value));
    }
}
