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
        public readonly string $pattern = 'none',
        public readonly int $patternOpacity = ContentSection::DEFAULT_PATTERN_OPACITY,
        public readonly int $patternScale = ContentSection::DEFAULT_PATTERN_SCALE,
        public readonly string $patternColor = ContentSection::DEFAULT_PATTERN_COLOR,
        public readonly ?string $patternCustomColor = null,
        public readonly bool $patternAnimated = false,
        public readonly string $patternMotion = ContentSection::DEFAULT_PATTERN_MOTION,
        public readonly int $patternSpeed = ContentSection::DEFAULT_PATTERN_SPEED,
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
            pattern: (string) (setting("{$sectionKey}_background_pattern") ?: 'none'),
            patternOpacity: (int) setting("{$sectionKey}_background_pattern_opacity", ContentSection::DEFAULT_PATTERN_OPACITY),
            patternScale: (int) setting("{$sectionKey}_background_pattern_scale", ContentSection::DEFAULT_PATTERN_SCALE),
            patternColor: (string) (setting("{$sectionKey}_background_pattern_color") ?: ContentSection::DEFAULT_PATTERN_COLOR),
            patternCustomColor: setting("{$sectionKey}_background_pattern_custom_color") ?: null,
            patternAnimated: setting_bool("{$sectionKey}_background_pattern_animated", false),
            patternMotion: (string) (setting("{$sectionKey}_background_pattern_motion") ?: ContentSection::DEFAULT_PATTERN_MOTION),
            patternSpeed: (int) setting("{$sectionKey}_background_pattern_speed", ContentSection::DEFAULT_PATTERN_SPEED),
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
            pattern: (string) ($section->background_pattern ?: 'none'),
            patternOpacity: (int) ($section->background_pattern_opacity ?? ContentSection::DEFAULT_PATTERN_OPACITY),
            patternScale: (int) ($section->background_pattern_scale ?? ContentSection::DEFAULT_PATTERN_SCALE),
            patternColor: (string) ($section->background_pattern_color ?: ContentSection::DEFAULT_PATTERN_COLOR),
            patternCustomColor: $section->background_pattern_custom_color ?: null,
            patternAnimated: (bool) $section->background_pattern_animated,
            patternMotion: (string) ($section->background_pattern_motion ?: ContentSection::DEFAULT_PATTERN_MOTION),
            patternSpeed: (int) ($section->background_pattern_speed ?? ContentSection::DEFAULT_PATTERN_SPEED),
        );
    }

    /**
     * Latar satu blok konten, dibaca dari kunci JSON-nya. Bentuk pengaturannya
     * sama dengan seksi dinamis, hanya sumbernya array dan bukan record.
     *
     * @param  array<string, mixed>  $block
     * @param  string  $baseMode  latar untuk pilihan "Abu Lembut"; seksi berkartu
     *                            memakai putih agar kartunya lepas dari latar halaman
     */
    public static function fromBlock(array $block, string $baseMode = 'base'): self
    {
        return new self(
            mode: match ($block['background'] ?? 'default') {
                'image' => 'image',
                'alt' => 'alt',
                default => $baseMode,
            },
            image: $block['background_image'] ?? null,
            blur: (int) ($block['background_blur'] ?? 0),
            overlay: (int) ($block['background_overlay'] ?? 0),
            parallaxMode: (string) (($block['background_parallax_mode'] ?? null) ?: 'none'),
            parallaxSpeed: (int) ($block['background_parallax_speed'] ?? 30),
            lightText: (bool) ($block['background_light_text'] ?? true),
            pattern: (string) (($block['background_pattern'] ?? null) ?: 'none'),
            patternOpacity: (int) ($block['background_pattern_opacity'] ?? ContentSection::DEFAULT_PATTERN_OPACITY),
            patternScale: (int) ($block['background_pattern_scale'] ?? ContentSection::DEFAULT_PATTERN_SCALE),
            patternColor: (string) (($block['background_pattern_color'] ?? null) ?: ContentSection::DEFAULT_PATTERN_COLOR),
            patternCustomColor: $block['background_pattern_custom_color'] ?? null,
            patternAnimated: (bool) ($block['background_pattern_animated'] ?? false),
            patternMotion: (string) (($block['background_pattern_motion'] ?? null) ?: ContentSection::DEFAULT_PATTERN_MOTION),
            patternSpeed: (int) ($block['background_pattern_speed'] ?? ContentSection::DEFAULT_PATTERN_SPEED),
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
     * Pola menghias latar polos, jadi ia mengalah pada latar gambar.
     */
    public function hasPattern(): bool
    {
        return ! $this->hasImage() && SectionPatterns::exists($this->pattern);
    }

    public function patternMaskUrl(): ?string
    {
        return $this->hasPattern() ? SectionPatterns::maskUrl($this->pattern) : null;
    }

    public function patternSize(): ?string
    {
        return $this->hasPattern() ? $this->patternTileWidth().'px '.$this->patternTileHeight().'px' : null;
    }

    /**
     * Ukuran ubin setelah diskalakan pilihan admin. Ukuran mask dan jarak
     * tempuh animasi sama-sama dihitung dari sini — kalau keduanya berbeda
     * walau sepiksel, pola akan tampak melompat tiap kali animasinya mengulang.
     */
    public function patternTileWidth(): int
    {
        return $this->scaled(SectionPatterns::width($this->pattern));
    }

    public function patternTileHeight(): int
    {
        return $this->scaled(SectionPatterns::height($this->pattern));
    }

    private function scaled(int $size): int
    {
        $scale = array_key_exists($this->patternScale, ContentSection::PATTERN_SCALES)
            ? $this->patternScale
            : ContentSection::DEFAULT_PATTERN_SCALE;

        return max(1, (int) round($size * $scale / 100));
    }

    /**
     * Warna pola sebagai nilai CSS. Pilihan bertoken (`primary`, `text`, `muted`)
     * sengaja diteruskan sebagai `var(...)` supaya ikut berubah begitu admin
     * mengganti warna tema, tanpa perlu menyunting ulang tiap seksi.
     *
     * Hex kiriman admin disaring ketat sebelum masuk atribut `style`: nilai yang
     * tidak berbentuk hex ditolak dan jatuh ke warna utama, sebab apa pun yang
     * lolos dari sini akan ditanam mentah-mentah ke dalam CSS.
     */
    public function patternColorCss(): string
    {
        return match ($this->patternColor) {
            'text' => 'var(--text)',
            'muted' => 'var(--muted)',
            'white' => '#ffffff',
            'custom' => $this->safeCustomColor() ?? 'var(--primary)',
            default => 'var(--primary)',
        };
    }

    private function safeCustomColor(): ?string
    {
        $color = trim((string) $this->patternCustomColor);

        return preg_match('/^#(?:[0-9a-fA-F]{3,4}|[0-9a-fA-F]{6}|[0-9a-fA-F]{8})$/', $color) === 1
            ? $color
            : null;
    }

    /**
     * Kepekatan pola sebagai nilai opacity CSS (0–1).
     */
    public function patternOpacityValue(): float
    {
        return round(min(100, max(0, $this->patternOpacity)) / 100, 2);
    }

    /**
     * Gerak pola yang benar-benar dipakai, atau `none` bila animasinya mati,
     * seksinya tak berpola, atau pilihannya tidak dikenal.
     */
    public function resolvedPatternMotion(): string
    {
        if (! $this->hasPattern() || ! $this->patternAnimated) {
            return 'none';
        }

        return array_key_exists($this->patternMotion, ContentSection::PATTERN_MOTIONS)
            ? $this->patternMotion
            : 'none';
    }

    /**
     * Gerak yang berjalan sendiri lewat animasi CSS (bukan yang mengikuti guliran).
     */
    public function usesPatternAnimation(): bool
    {
        return in_array($this->resolvedPatternMotion(), ['drift', 'drift_x', 'pulse'], true);
    }

    /**
     * Pola bergeser mengikuti posisi guliran halaman; tidak bergerak saat diam.
     */
    public function usesPatternScrollMotion(): bool
    {
        return $this->resolvedPatternMotion() === 'scroll';
    }

    /**
     * Laju gerak pola (piksel per detik), dibatasi ke rentang pilihan admin.
     */
    public function patternSpeed(): int
    {
        $speeds = array_keys(ContentSection::PATTERN_SPEEDS);

        return min(max($speeds), max(min($speeds), $this->patternSpeed));
    }

    /**
     * Lama satu putaran animasi: jarak tempuh dibagi lajunya. Dihitung, bukan
     * dipilih langsung, supaya pola berubin kecil dan berubin besar terasa
     * mengalir sama cepatnya.
     */
    public function patternDuration(): float
    {
        $travel = match ($this->resolvedPatternMotion()) {
            'drift_x' => $this->patternTravelX(),
            default => max($this->patternTravelX(), $this->patternTravelY()),
        };

        return max(0.5, round($travel / $this->patternSpeed(), 2));
    }

    /**
     * Lama satu tarikan napas "denyut". Denyut tidak menempuh jarak, jadi
     * lajunya diterjemahkan jadi periode: makin cepat lajunya, makin rapat
     * naik-turun kepekatannya.
     */
    public function patternPulseDuration(): int
    {
        return max(3, min(24, (int) round(72 / $this->patternSpeed())));
    }

    /**
     * Jarak tempuh satu putaran: kelipatan bulat ukuran ubin, agar polanya
     * kembali segaris saat animasi mengulang. Gerak yang mengikuti guliran
     * memakai beberapa ubin sekaligus supaya pergeserannya terasa sepanjang
     * seksi dilewati.
     */
    public function patternTravelX(): int
    {
        return $this->hasPattern() ? $this->patternTileWidth() * $this->patternTravelSteps() : 0;
    }

    public function patternTravelY(): int
    {
        return $this->hasPattern() ? $this->patternTileHeight() * $this->patternTravelSteps() : 0;
    }

    private function patternTravelSteps(): int
    {
        return $this->usesPatternScrollMotion() ? 4 : 1;
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
            // Pola pun dilukis di lapisan latar, jadi seksinya ikut transparan
            $this->hasImage(), $this->hasPattern() => 'transparent',
            default => $this->baseColor($default),
        };
    }

    /**
     * Warna dasar seksi tanpa memperhitungkan gambar atau pola di atasnya.
     *
     * @param  string|null  $default  latar rancangan seksi itu sendiri
     */
    public function baseColor(?string $default = null): string
    {
        return match ($this->mode) {
            'base' => 'var(--bg)',
            'alt' => 'var(--bg-alt, var(--bg))',
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
