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

    /** @var array<string, string> */
    public const BACKGROUNDS = [
        'default' => 'Polos',
        'alt' => 'Abu Lembut',
    ];

    protected $fillable = [
        'eyebrow',
        'title',
        'description',
        'image',
        'image_position',
        'background',
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
