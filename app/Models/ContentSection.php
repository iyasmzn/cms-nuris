<?php

namespace App\Models;

use Database\Factories\ContentSectionFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Seksi bebas di halaman depan: satu kartu gambar (kiri atau kanan) berdampingan
 * dengan judul, deskripsi, dan tombol CTA. Urutannya diatur bersama seksi bawaan
 * lewat Pengaturan Halaman Depan.
 */
class ContentSection extends Model
{
    /** @use HasFactory<ContentSectionFactory> */
    use HasFactory;

    /**
     * Prefix kunci seksi ini di dalam setting `section_order`.
     */
    public const ORDER_KEY_PREFIX = 'content_section_';

    /** @var array<string, string> */
    public const IMAGE_POSITIONS = [
        'left' => 'Gambar di Kiri',
        'right' => 'Gambar di Kanan',
    ];

    /**
     * Latar seksi. `default` memakai token `--bg` (#f5f5f7, abu lembut) seperti
     * seksi bawaan lain, `alt` memakai `--bg-alt` (#ffffff, putih bersih).
     *
     * @var array<string, string>
     */
    public const BACKGROUNDS = [
        'default' => 'Abu Lembut',
        'alt' => 'Putih Bersih',
        'image' => 'Gambar',
    ];

    /**
     * Radius blur latar (px) → label.
     *
     * @var array<int, string>
     */
    public const BLUR_LEVELS = [
        0 => 'Tanpa Blur',
        4 => 'Ringan',
        8 => 'Sedang',
        16 => 'Kuat',
    ];

    /**
     * Kepekatan lapisan gelap (persen) → label.
     *
     * @var array<int, string>
     */
    public const OVERLAY_LEVELS = [
        0 => 'Tanpa Lapisan Gelap',
        25 => 'Sedikit Gelap',
        45 => 'Sedang',
        65 => 'Gelap',
        80 => 'Sangat Gelap',
    ];

    /**
     * Cara gambar latar bereaksi terhadap guliran halaman.
     *
     * @var array<string, string>
     */
    public const PARALLAX_MODES = [
        'none' => 'Ikut Menggulir',
        'scroll' => 'Bergeser (Parallax)',
        'fixed' => 'Diam (Terkunci ke Layar)',
    ];

    /**
     * Batas kekuatan parallax yang bisa dipilih admin lewat slider (persen).
     */
    public const PARALLAX_MIN_SPEED = 1;

    public const PARALLAX_MAX_SPEED = 100;

    protected $fillable = [
        'eyebrow',
        'title',
        'description',
        'image',
        'image_position',
        'background',
        'background_image',
        'background_blur',
        'background_overlay',
        'background_parallax_mode',
        'background_parallax_speed',
        'background_light_text',
        'anchor',
        'cta_label',
        'cta_url',
        'cta_new_tab',
        'is_published',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'background_blur' => 'integer',
            'background_overlay' => 'integer',
            'background_parallax_speed' => 'integer',
            'background_light_text' => 'boolean',
            'cta_new_tab' => 'boolean',
            'is_published' => 'boolean',
        ];
    }

    /**
     * Kunci seksi ini pada daftar urutan halaman depan.
     */
    public function getOrderKeyAttribute(): string
    {
        return self::ORDER_KEY_PREFIX.$this->id;
    }

    /**
     * ID anchor yang aman dipakai sebagai target tautan menu.
     */
    public function getAnchorIdAttribute(): string
    {
        return Str::slug($this->anchor ?: 'seksi-'.$this->id);
    }

    /**
     * URL gambar dari storage, atau null bila seksi tampil tanpa gambar.
     */
    public function getImageUrlAttribute(): ?string
    {
        return $this->image ? asset('storage/'.$this->image) : null;
    }

    /**
     * Latar gambar hanya aktif bila modenya dipilih dan gambarnya terisi.
     */
    public function getHasBackgroundImageAttribute(): bool
    {
        return $this->background === 'image' && filled($this->background_image);
    }

    /**
     * URL gambar latar dari storage, atau null bila seksi tidak memakainya.
     */
    public function getBackgroundImageUrlAttribute(): ?string
    {
        return $this->has_background_image ? asset('storage/'.$this->background_image) : null;
    }

    /**
     * Kepekatan lapisan gelap sebagai nilai opacity CSS (0–1).
     */
    public function getOverlayOpacityAttribute(): float
    {
        return round(min(100, max(0, (int) $this->background_overlay)) / 100, 2);
    }

    /**
     * Intensitas parallax sebagai pengali pergeseran (0–1).
     */
    public function getParallaxFactorAttribute(): float
    {
        $speed = min(self::PARALLAX_MAX_SPEED, max(0, (int) $this->background_parallax_speed));

        return round($speed / 100, 2);
    }

    /**
     * Efek gulir hanya berlaku bila seksi benar-benar punya latar gambar.
     */
    public function getUsesScrollParallaxAttribute(): bool
    {
        return $this->has_background_image && $this->background_parallax_mode === 'scroll';
    }

    /**
     * Latar terkunci ke layar; kontennya yang meluncur di atasnya.
     */
    public function getUsesFixedBackgroundAttribute(): bool
    {
        return $this->has_background_image && $this->background_parallax_mode === 'fixed';
    }

    /**
     * Teks putih dipakai hanya di atas latar gambar.
     */
    public function getUsesLightTextAttribute(): bool
    {
        return $this->has_background_image && $this->background_light_text;
    }

    /**
     * Tombol CTA hanya dirender bila label dan tujuannya terisi.
     */
    public function getHasCtaAttribute(): bool
    {
        return filled($this->cta_label) && filled($this->cta_url);
    }

    /**
     * Ambil id dari kunci urutan, misal `content_section_7` → 7.
     */
    public static function idFromOrderKey(string $key): ?int
    {
        if (! str_starts_with($key, self::ORDER_KEY_PREFIX)) {
            return null;
        }

        $id = substr($key, strlen(self::ORDER_KEY_PREFIX));

        return ctype_digit($id) ? (int) $id : null;
    }

    /**
     * @return Builder<static>
     */
    public static function published(): Builder
    {
        return static::where('is_published', true)->orderBy('sort_order')->orderBy('id');
    }

    /**
     * @return Builder<static>
     */
    public static function ordered(): Builder
    {
        return static::orderBy('sort_order')->orderBy('id');
    }
}
