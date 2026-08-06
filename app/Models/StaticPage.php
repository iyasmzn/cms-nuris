<?php

namespace App\Models;

use App\Support\ContentBlockText;
use App\Support\PageHero;
use Database\Factories\StaticPageFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StaticPage extends Model
{
    /** @use HasFactory<StaticPageFactory> */
    use HasFactory;

    protected $fillable = [
        'title',
        'slug',
        'meta_description',
        'content',
        'blocks',
        'hero',
        'show_sidebar',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'show_sidebar' => 'boolean',
        'blocks' => 'array',
        'hero' => 'array',
    ];

    /**
     * Cover hero halaman: gambar, berkas video, atau video YouTube.
     */
    public function getHeroCoverAttribute(): PageHero
    {
        return PageHero::fromArray($this->hero);
    }

    /**
     * Ringkasan untuk meta description. Halaman yang tidak mengisi meta sendiri
     * tetap mendapat deskripsi dari teks seksinya, bukan string kosong.
     */
    public function getSeoDescriptionAttribute(): string
    {
        return filled($this->meta_description)
            ? $this->meta_description
            : ContentBlockText::summary($this->blocks);
    }

    /**
     * Judul seksi & heading di dalamnya, untuk daftar isi di sidebar.
     *
     * @return list<string>
     */
    public function getContentHeadingsAttribute(): array
    {
        return ContentBlockText::headings($this->blocks);
    }

    /**
     * Slug segmen-teratas yang sudah dipakai route lain atau panel admin,
     * sehingga tidak boleh dipakai halaman statis agar URL tidak bertabrakan.
     *
     * @return list<string>
     */
    public static function reservedSlugs(): array
    {
        return [
            'admin', 'blog', 'guru', 'ppdb', 'panitia', 'unduhan',
            'masuk', 'daftar', 'keluar', 'auth', 'email', 'profil',
            'kegiatan', 'program', 'cerita-santri', 'kontak', 'galeri',
            'sitemap.xml', 'storage', 'livewire', 'up', 'api', 'page',
        ];
    }

    /**
     * Regex constraint untuk route root `/{slug}`: hanya cocok satu segmen
     * bersih (huruf, angka, tanda hubung) dan menolak seluruh reserved slug,
     * sehingga catch-all tidak pernah menaungi route lain maupun panel admin.
     */
    public static function rootSlugConstraint(): string
    {
        $reserved = implode('|', array_map('preg_quote', static::reservedSlugs()));

        return '(?!(?:'.$reserved.')$)[A-Za-z0-9\-]+';
    }

    // ── Scopes ──────────────────────────────────────────────

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('title');
    }
}
