<?php

namespace App\Models;

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
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'blocks' => 'array',
    ];

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
