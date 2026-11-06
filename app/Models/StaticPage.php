<?php

namespace App\Models;

use App\Support\ContentBlockText;
use App\Support\LandingHero;
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
     * Teks hero landing bila kepala halamannya memakai gaya "Hero Landing",
     * atau null untuk kepala halaman standar.
     */
    public function getLandingHeroAttribute(): ?LandingHero
    {
        return LandingHero::isEnabled($this->hero)
            ? LandingHero::fromArray($this->hero, (string) $this->title)
            : null;
    }

    /**
     * Ringkasan untuk meta description. Halaman yang tidak mengisi meta sendiri
     * tetap mendapat deskripsi dari teks seksinya, bukan string kosong.
     *
     * @param  array<int, mixed>|null  $blocks  seksi yang benar-benar tampil (hasil
     *                                          LandingBlocks::prepare()), agar judul blok data
     *                                          yang sedang kosong tidak ikut; bawaannya seksi tersimpan
     */
    public function seoDescription(?array $blocks = null): string
    {
        return filled($this->meta_description)
            ? $this->meta_description
            : ContentBlockText::summary($blocks ?? $this->blocks);
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
